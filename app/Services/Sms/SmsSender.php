<?php

namespace App\Services\Sms;

use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Jobs\SendSms;
use App\Models\SmsMessage;
use App\Support\SmsText;

/**
 * Records an SMS in the delivery log and queues it for the configured driver.
 */
class SmsSender
{
    /**
     * A one-time sign-in code ("otp.*" purposes). Its text is never stored.
     */
    public function send(string $phone, string $purpose, string $text): SmsMessage
    {
        return $this->queue($text, [
            'phone' => $phone,
            'purpose' => $purpose,
        ]);
    }

    /**
     * A message written by staff (single send, campaign test). The text is kept in the history.
     *
     * @param  array<string, mixed>  $attributes  user_id, sent_by, campaign_id
     */
    public function sendText(string $phone, string $text, string $purpose = SmsMessage::PURPOSE_SINGLE, array $attributes = []): SmsMessage
    {
        return $this->queue($text, [
            ...$attributes,
            'phone' => $phone,
            'purpose' => $purpose,
            'body' => $text,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function queue(string $text, array $attributes): SmsMessage
    {
        $message = SmsMessage::query()->create([
            ...$attributes,
            'driver' => config('sms.default'),
            'status' => SmsStatus::Queued,
            'segments' => SmsText::segments($text),
        ]);

        try {
            SendSms::dispatch($message->id, $text)->onQueue(config('sms.queue'));
        } catch (SmsDeliveryFailed $exception) {
            // Only reached with the sync queue; the job has already marked the message as failed.
            report($exception);
        }

        return $message->refresh();
    }
}
