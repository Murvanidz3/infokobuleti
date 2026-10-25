<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Delivery receipts from Twilio, verified with the X-Twilio-Signature header.
 *
 * @see https://www.twilio.com/docs/usage/webhooks/webhooks-security
 */
class TwilioStatusController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = (string) config('sms.drivers.twilio.auth_token');

        abort_if($token === '' || ! self::validSignature($request, $token), 403);

        $status = match ($request->string('MessageStatus')->toString()) {
            'delivered' => SmsStatus::Delivered,
            'failed', 'undelivered' => SmsStatus::Failed,
            default => null,
        };

        if ($status !== null) {
            SmsMessage::query()->where('provider_message_id', $request->string('MessageSid')->toString())->update(array_filter([
                'status' => $status,
                'delivered_at' => $status === SmsStatus::Delivered ? now() : null,
                'error' => $status === SmsStatus::Failed ? trim($request->string('ErrorCode').' '.$request->string('ErrorMessage')) : null,
            ], fn ($value) => $value !== null));
        }

        return response()->noContent();
    }

    public static function validSignature(Request $request, string $token): bool
    {
        $params = $request->request->all();
        ksort($params);

        $data = $request->fullUrl();

        foreach ($params as $key => $value) {
            $data .= $key.(is_array($value) ? implode('', $value) : $value);
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $token, true));

        return hash_equals($expected, (string) $request->header('X-Twilio-Signature'));
    }
}
