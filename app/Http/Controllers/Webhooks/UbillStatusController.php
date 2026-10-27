<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Sms\DeliveryReports;
use App\Services\Sms\SmsManager;
use App\Services\Sms\UbillSmsGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Callbacks from UBill: a GET with the callback key set in the UBill API app and an "event".
 * Delivery updates (sms.delivery.updated, or no event) carry smsID, number and statusID
 * (0 sent, 1 received, 2 not delivered, 3 pending, 4 error); sms.brand.activated means a sender
 * name was approved. Any 2xx answer counts as received.
 */
class UbillStatusController extends Controller
{
    public function __invoke(Request $request, DeliveryReports $reports, SmsManager $sms): Response
    {
        $key = (string) config('sms.drivers.ubill.callback_key');

        abort_if($key === '' || ! hash_equals($key, $request->string('key')->toString()), 403);

        $event = $request->string('event')->toString();

        if ($event === 'sms.brand.activated') {
            try {
                $gateway = $sms->driver('ubill');

                if ($gateway instanceof UbillSmsGateway) {
                    $gateway->forgetBrand();
                }
            } catch (Throwable) {
                // Not configured: nothing cached either.
            }

            return response()->noContent();
        }

        $smsId = $request->string('smsID')->toString();

        if (($event === '' || $event === 'sms.delivery.updated') && $smsId !== '' && $request->has('statusID')) {
            $number = $request->string('number')->toString();
            $reports->apply('ubill', $smsId, [$number !== '' ? $number : 'unknown' => $request->integer('statusID')]);
        }

        return response()->noContent();
    }
}
