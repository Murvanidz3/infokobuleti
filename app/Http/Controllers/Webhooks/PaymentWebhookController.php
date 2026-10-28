<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Billing\HandlePaymentCallback;
use App\Enums\PaymentGateway as GatewayName;
use App\Exceptions\InvalidPaymentCallback;
use App\Exceptions\PaymentGatewayError;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Server-to-server payment notifications from the banks. Answers 200 once the message is
 * accepted (also for unknown orders, so the bank stops retrying), 403 for a bad signature and
 * 503 when the bank's status API is down (the bank retries later).
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayManager $gateways, HandlePaymentCallback $handle): Response
    {
        $name = GatewayName::tryFrom($gateway);

        abort_if($name === null || ! $gateways->isEnabled($name), 404);

        try {
            $handle->handle($name, $request);
        } catch (InvalidPaymentCallback) {
            abort(403);
        } catch (PaymentGatewayError $e) {
            report($e);

            abort(503);
        }

        return response('OK', 200);
    }
}
