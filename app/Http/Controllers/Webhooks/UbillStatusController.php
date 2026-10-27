<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Delivery reports from UBill: a GET with the callback key set in the UBill API app, smsID,
 * number, statusID (0 sent, 1 received, 2 not delivered, 3 pending, 4 error) and date. Any 2xx
 * answer counts as received.
 */
class UbillStatusController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $key = (string) config('sms.drivers.ubill.callback_key');

        abort_if($key === '' || ! hash_equals($key, $request->string('key')->toString()), 403);

        $status = match ($request->integer('statusID', -1)) {
            1 => SmsStatus::Delivered,
            2, 4 => SmsStatus::Failed,
            default => null,
        };

        $smsId = $request->string('smsID')->toString();

        if ($status !== null && $smsId !== '') {
            SmsMessage::query()
                ->where('driver', 'ubill')
                ->where('provider_message_id', $smsId)
                ->whereNot('status', SmsStatus::Delivered)
                ->update(array_filter([
                    'status' => $status,
                    'delivered_at' => $status === SmsStatus::Delivered ? now() : null,
                    'error' => $status === SmsStatus::Failed ? 'UBill status '.$request->integer('statusID') : null,
                ], fn ($value) => $value !== null));
        }

        return response()->noContent();
    }
}
