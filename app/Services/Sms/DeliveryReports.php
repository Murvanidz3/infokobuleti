<?php

namespace App\Services\Sms;

use App\Contracts\SmsProviderAccount;
use App\Enums\SmsStatus;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Delivery status from the provider, by callback (one number) or by asking for the report of a
 * message or batch. A delivered message never goes back to another status.
 */
class DeliveryReports
{
    public function __construct(private SmsManager $sms) {}

    /**
     * @param  array<string, int>  $statuses  number (any format) => provider report code
     * @return int messages changed
     */
    public function apply(string $driver, string $providerMessageId, array $statuses): int
    {
        $changed = 0;
        $campaigns = [];

        $messages = SmsMessage::query()
            ->where('driver', $driver)
            ->where('provider_message_id', $providerMessageId)
            ->whereNot('status', SmsStatus::Delivered)
            ->get(['id', 'phone', 'status', 'campaign_id']);

        $byNumber = [];

        foreach ($statuses as $number => $code) {
            $byNumber[UbillSmsGateway::local((string) $number)] = $code;
        }

        foreach ($messages as $message) {
            // A single message: the report's only row is its status, whatever number format.
            $code = $byNumber[UbillSmsGateway::local($message->phone)] ?? (count($statuses) === 1 && $messages->count() === 1 ? reset($statuses) : null);
            $status = self::status($code);

            $attributes = ['checked_at' => now()];

            if ($status !== null && $status !== $message->status) {
                $attributes += [
                    'status' => $status,
                    'delivered_at' => $status === SmsStatus::Delivered ? now() : null,
                    'error' => $status === SmsStatus::Failed ? "Provider status {$code}" : null,
                ];
                $changed++;
            }

            SmsMessage::query()->whereKey($message->id)->update($attributes);

            if ($message->campaign_id !== null) {
                $campaigns[$message->campaign_id] = true;
            }
        }

        foreach (SmsCampaign::query()->whereKey(array_keys($campaigns))->get() as $campaign) {
            $campaign->refreshCounters();
        }

        return $changed;
    }

    /**
     * Asks the provider for the reports of recent messages that are still "sent".
     *
     * @param  Builder<SmsMessage>|null  $scope  limit to some messages (admin "refresh")
     * @return int messages changed
     */
    public function sync(?Builder $scope = null, int $limit = 50): int
    {
        $driver = (string) config('sms.default');

        try {
            $gateway = $this->sms->driver($driver);
        } catch (Throwable) {
            return 0;
        }

        if (! $gateway instanceof SmsProviderAccount) {
            return 0;
        }

        $ids = ($scope ?? SmsMessage::query()->where('sent_at', '>=', now()->subDays(3))->where(fn (Builder $query) => $query->whereNull('checked_at')->orWhere('checked_at', '<=', now()->subMinutes(10))))
            ->where('driver', $driver)
            ->where('status', SmsStatus::Sent)
            ->whereNotNull('provider_message_id')
            ->distinct()
            ->limit($limit)
            ->pluck('provider_message_id');

        $changed = 0;

        foreach ($ids as $id) {
            try {
                $report = $gateway->report((string) $id);
            } catch (Throwable $exception) {
                report($exception);

                continue;
            }

            $changed += $this->apply($driver, (string) $id, $report);
        }

        return $changed;
    }

    public static function status(?int $code): ?SmsStatus
    {
        return match ($code) {
            SmsProviderAccount::REPORT_DELIVERED => SmsStatus::Delivered,
            SmsProviderAccount::REPORT_UNDELIVERED, SmsProviderAccount::REPORT_ERROR => SmsStatus::Failed,
            default => null,
        };
    }
}
