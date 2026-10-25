<?php

namespace App\Services\Sms;

use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Jobs\SendSms;
use App\Models\SmsMessage;

/**
 * Records an SMS in the delivery log and queues it for the configured driver.
 */
class SmsSender
{
    public function send(string $phone, string $purpose, string $text): SmsMessage
    {
        $message = SmsMessage::query()->create([
            'phone' => $phone,
            'purpose' => $purpose,
            'driver' => config('sms.default'),
            'status' => SmsStatus::Queued,
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
