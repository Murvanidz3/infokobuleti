<?php

namespace App\Jobs;

use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Models\SmsMessage;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsOptions;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Sends one logged SMS. The payload is encrypted on the queue because the text may hold a login code.
 */
class SendSms implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /** A login code is useless after a few minutes, so retries stay short. */
    public int $tries = 2;

    public int $backoff = 5;

    public function __construct(public int $smsMessageId, public string $text) {}

    public function handle(SmsManager $sms): void
    {
        $message = SmsMessage::query()->find($this->smsMessageId);

        if ($message === null || $message->status !== SmsStatus::Queued) {
            return;
        }

        try {
            // Sign-in codes go with the provider's OTP priority; other messages never do.
            $options = $message->isOneTimeCode() ? SmsOptions::otp() : new SmsOptions;
            $providerId = $sms->driver($message->driver)->send($message->phone, $this->text, $options);
        } catch (SmsDeliveryFailed $exception) {
            $message->update(['error' => $exception->getMessage()]);

            throw $exception;
        }

        $message->update([
            'status' => SmsStatus::Sent,
            'provider_message_id' => $providerId,
            'error' => null,
            'sent_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        SmsMessage::query()->whereKey($this->smsMessageId)->update([
            'status' => SmsStatus::Failed,
            'error' => $exception?->getMessage(),
        ]);
    }
}
