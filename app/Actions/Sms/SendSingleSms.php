<?php

namespace App\Actions\Sms;

use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Support\GeorgianPhone;
use Illuminate\Validation\ValidationException;

/**
 * One message written by staff to one number (admin "Send SMS"). It goes out even to numbers on
 * the opt-out list (that list is for campaigns); the text is kept in the history.
 */
class SendSingleSms
{
    public function __construct(private SmsSender $sender) {}

    public function handle(string $phone, string $text, User $staff, ?User $recipient = null): SmsMessage
    {
        $e164 = GeorgianPhone::normalize($phone);
        $text = trim($text);

        if ($e164 === null) {
            throw ValidationException::withMessages(['phone' => __('sms.errors.invalid_phone')]);
        }

        if ($text === '') {
            throw ValidationException::withMessages(['body' => __('sms.errors.empty_text')]);
        }

        $recipient ??= User::query()->where('phone', $e164)->first();

        $message = $this->sender->sendText($e164, $text, SmsMessage::PURPOSE_SINGLE, [
            'user_id' => $recipient?->id,
            'sent_by' => $staff->id,
        ]);

        activity('admin')->causedBy($staff)->event('sms_sent')
            ->withProperties(['phone' => GeorgianPhone::mask($e164), 'message_id' => $message->id])
            ->log('sms sent');

        return $message;
    }
}
