<?php

namespace App\Services\Sms;

use App\Contracts\SmsProviderAccount;
use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use App\Enums\SmsStatus;
use App\Jobs\SendSmsCampaign;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Settings;
use App\Support\SmsText;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Campaign life cycle: draft (recipients resolved and checked) → scheduled → sending → sent,
 * plus cancel, test send, duplicate and "send again to the failed numbers".
 */
class Campaigns
{
    public function __construct(
        private RecipientImport $import,
        private SmsSender $sender,
        private SmsManager $sms,
        private Settings $settings,
    ) {}

    /**
     * Resolves the recipients from the campaign's audience options and stores the snapshot,
     * the import report and the SMS estimate. Only while the campaign can still change.
     */
    public function prepare(SmsCampaign $campaign): SmsCampaign
    {
        if (! $campaign->status->isEditable()) {
            throw new RuntimeException('The campaign was already sent.');
        }

        $options = $campaign->audience_options ?? [];

        $result = match ($campaign->audience) {
            SmsAudience::Manual => $this->import->fromText((string) ($options['numbers'] ?? '')),
            SmsAudience::Csv => filled($options['csv'] ?? null) && Storage::disk('local')->exists((string) $options['csv'])
                ? $this->import->fromCsv(Storage::disk('local')->path((string) $options['csv']))
                : $this->import->fromText(''),
            SmsAudience::Users => $this->import->fromUsers($options),
        };

        $max = (int) config('sms.manager.max_recipients');
        $recipients = $result['recipients'];
        $report = $result['report'];

        if (count($recipients) > $max) {
            $report['over_limit'] = count($recipients) - $max;
            $recipients = array_slice($recipients, 0, $max);
        }

        $segments = array_map(fn (array $recipient): int => SmsText::segments($this->textFor($campaign, $recipient)), $recipients);
        $report['sms_total'] = array_sum($segments);

        $campaign->forceFill([
            'recipients' => $recipients,
            'recipients_count' => count($recipients),
            'segments' => max([SmsText::segments($this->textFor($campaign, ['phone' => '+995555000000', 'name' => null])), ...$segments]),
            'import_report' => $report,
        ])->save();

        return $campaign;
    }

    /**
     * The text one recipient gets: placeholders filled, the opt-out footer added when wanted.
     *
     * @param  array{phone: string, name?: string|null}  $recipient
     */
    public function textFor(SmsCampaign $campaign, array $recipient): string
    {
        $text = SmsText::render($campaign->body, $recipient);
        $footer = trim((string) $this->settings->get('sms.campaign_footer'));

        return $campaign->append_footer && $footer !== '' ? $text."\n".$footer : $text;
    }

    /**
     * Sends now, or at $at when it is in the future.
     */
    public function start(SmsCampaign $campaign, ?CarbonInterface $at = null): SmsCampaign
    {
        if (! $campaign->status->isEditable()) {
            throw new RuntimeException('The campaign was already sent.');
        }

        if ($campaign->recipients_count === 0) {
            throw new RuntimeException(__('sms.errors.no_recipients'));
        }

        if ($at !== null && $at->isFuture()) {
            $campaign->forceFill(['status' => SmsCampaignStatus::Scheduled, 'scheduled_at' => $at])->save();
            $this->log($campaign, 'sms_campaign_scheduled');

            return $campaign;
        }

        $balance = $this->balance();

        if ($balance !== null && $balance < $campaign->estimatedSms()) {
            throw new RuntimeException(__('sms.errors.low_balance', ['needed' => $campaign->estimatedSms(), 'balance' => $balance]));
        }

        $campaign->forceFill(['status' => SmsCampaignStatus::Sending, 'started_at' => now(), 'error' => null])->save();
        $this->log($campaign, 'sms_campaign_started');

        SendSmsCampaign::dispatch($campaign->id)->onQueue(config('sms.queue'));

        return $campaign->refresh();
    }

    /**
     * Campaigns whose time has come (scheduler, every minute).
     */
    public function startDue(): int
    {
        $started = 0;

        foreach (SmsCampaign::query()->where('status', SmsCampaignStatus::Scheduled)->where('scheduled_at', '<=', now())->get() as $campaign) {
            try {
                $this->start($campaign->forceFill(['scheduled_at' => null]));
                $started++;
            } catch (Throwable $exception) {
                $campaign->forceFill(['status' => SmsCampaignStatus::Failed, 'error' => $exception->getMessage(), 'finished_at' => now()])->save();
            }
        }

        return $started;
    }

    public function cancel(SmsCampaign $campaign): SmsCampaign
    {
        if (! $campaign->status->isEditable()) {
            throw new RuntimeException('The campaign was already sent.');
        }

        $campaign->forceFill(['status' => SmsCampaignStatus::Cancelled, 'scheduled_at' => null])->save();
        $this->log($campaign, 'sms_campaign_cancelled');

        return $campaign;
    }

    /**
     * Sends the text of the first recipient (or a sample) to one number.
     */
    public function sendTest(SmsCampaign $campaign, string $phone, ?User $by = null): SmsMessage
    {
        $sample = $campaign->recipients[0] ?? ['phone' => $phone, 'name' => null];

        return $this->sender->sendText($phone, $this->textFor($campaign, $sample), SmsMessage::PURPOSE_TEST, [
            'campaign_id' => $campaign->id,
            'sent_by' => $by?->id,
        ]);
    }

    /**
     * A new draft with the same text and audience.
     */
    public function duplicate(SmsCampaign $campaign, ?User $by = null, bool $onlyFailed = false): SmsCampaign
    {
        $copy = $campaign->replicate(['status', 'sent_count', 'delivered_count', 'failed_count', 'scheduled_at', 'started_at', 'finished_at', 'error', 'created_by', 'recipients', 'import_report', 'recipients_count']);
        $copy->forceFill([
            'name' => mb_substr(($onlyFailed ? __('sms.campaigns.retry_prefix') : __('sms.campaigns.copy_prefix')).' '.$campaign->name, 0, 120),
            'status' => SmsCampaignStatus::Draft,
            'created_by' => $by?->id,
        ]);

        if ($onlyFailed) {
            $numbers = $campaign->messages()->where('status', SmsStatus::Failed)->pluck('phone')->implode("\n");
            $copy->forceFill(['audience' => SmsAudience::Manual, 'audience_options' => ['numbers' => $numbers]]);
        } elseif ($copy->audience === SmsAudience::Csv && filled($path = $campaign->audience_options['csv'] ?? null) && Storage::disk('local')->exists((string) $path)) {
            // The copy gets its own file, so deleting one campaign keeps the other's list.
            $newPath = 'sms-imports/'.uniqid('copy-', true).'.csv';
            Storage::disk('local')->copy((string) $path, $newPath);
            $copy->forceFill(['audience_options' => [...($campaign->audience_options ?? []), 'csv' => $newPath]]);
        }

        $copy->save();

        return $this->prepare($copy);
    }

    /**
     * Balance of the configured provider; null when unknown (log driver, provider down).
     */
    public function balance(): ?int
    {
        try {
            $gateway = $this->sms->driver();

            return $gateway instanceof SmsProviderAccount ? $gateway->balance() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function log(SmsCampaign $campaign, string $event): void
    {
        activity('admin')->causedBy(auth()->user())->performedOn($campaign)->event($event)
            ->withProperties(['recipients' => $campaign->recipients_count, 'scheduled_at' => $campaign->scheduled_at?->toIso8601String()])
            ->log($event);
    }
}
