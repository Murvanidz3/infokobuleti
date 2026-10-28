<?php

namespace App\Jobs;

use App\Contracts\SendsBulkSms;
use App\Enums\SmsCampaignStatus;
use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\SmsOptOut;
use App\Services\Sms\Campaigns;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsOptions;
use App\Support\SmsText;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Sends a campaign in batches. Every recipient gets a row in the delivery log first; a batch
 * that fails is marked failed and the rest still goes out. Never retried automatically: a retry
 * could text people twice (use "send again to failed numbers" instead).
 */
class SendSmsCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $campaignId) {}

    public function handle(SmsManager $sms, Campaigns $campaigns): void
    {
        $campaign = DB::transaction(function (): ?SmsCampaign {
            $campaign = SmsCampaign::query()->lockForUpdate()->find($this->campaignId);

            // Only once: a campaign that already has messages is never sent again.
            if ($campaign === null || $campaign->status !== SmsCampaignStatus::Sending || $campaign->messages()->exists()) {
                return null;
            }

            return $campaign;
        });

        if ($campaign === null) {
            return;
        }

        $driver = (string) config('sms.default');
        $gateway = $sms->driver($driver);
        $options = SmsOptions::campaign($campaign->respect_stop_list);

        // Numbers that opted out after the campaign was prepared are skipped too.
        $optedOut = SmsOptOut::query()->whereIn('phone', array_column($campaign->recipients ?? [], 'phone'))->pluck('phone')->flip();
        $recipients = array_values(array_filter($campaign->recipients ?? [], fn (array $recipient): bool => ! $optedOut->has($recipient['phone'])));

        $size = $gateway instanceof SendsBulkSms ? max(1, $gateway->batchSize()) : 1;

        foreach (array_chunk($recipients, $size) as $chunk) {
            $now = now();
            $rows = array_map(fn (array $recipient): array => [
                'recipient' => $recipient,
                'text' => $text = $campaigns->textFor($campaign, $recipient),
                'message' => SmsMessage::query()->create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $recipient['user_id'] ?? null,
                    'sent_by' => $campaign->created_by,
                    'phone' => $recipient['phone'],
                    'purpose' => SmsMessage::PURPOSE_CAMPAIGN,
                    'body' => $text,
                    'segments' => SmsText::segments($text),
                    'driver' => $driver,
                    'status' => SmsStatus::Queued,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ], $chunk);

            $ids = array_map(fn (array $row): int => $row['message']->id, $rows);

            try {
                if ($gateway instanceof SendsBulkSms) {
                    $providerId = $gateway->sendBatch(array_map(fn (array $row): array => ['phone' => $row['recipient']['phone'], 'text' => $row['text']], $rows), $options);
                    SmsMessage::query()->whereKey($ids)->update(['status' => SmsStatus::Sent, 'provider_message_id' => $providerId, 'sent_at' => now()]);
                } else {
                    $row = $rows[0];
                    $providerId = $gateway->send($row['recipient']['phone'], $row['text'], $options);
                    $row['message']->update(['status' => SmsStatus::Sent, 'provider_message_id' => $providerId, 'sent_at' => now()]);
                }
            } catch (SmsDeliveryFailed $exception) {
                SmsMessage::query()->whereKey($ids)->update(['status' => SmsStatus::Failed, 'error' => mb_substr($exception->getMessage(), 0, 500)]);
                $campaign->forceFill(['error' => mb_substr($exception->getMessage(), 0, 500)])->save();
            }
        }

        $campaign->refreshCounters();
        $campaign->forceFill([
            'status' => $campaign->sent_count > 0 || $recipients === [] ? SmsCampaignStatus::Sent : SmsCampaignStatus::Failed,
            'finished_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $campaign = SmsCampaign::query()->find($this->campaignId);

        if ($campaign === null) {
            return;
        }

        $campaign->refreshCounters();
        $campaign->forceFill([
            'status' => $campaign->sent_count > 0 ? SmsCampaignStatus::Sent : SmsCampaignStatus::Failed,
            'error' => mb_substr((string) $exception?->getMessage(), 0, 500),
            'finished_at' => now(),
        ])->save();

        SmsMessage::query()->where('campaign_id', $campaign->id)->where('status', SmsStatus::Queued)
            ->update(['status' => SmsStatus::Failed, 'error' => 'Campaign job stopped']);
    }
}
