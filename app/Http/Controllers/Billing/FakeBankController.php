<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\HandlePaymentCallback;
use App\Enums\PaymentGateway as GatewayName;
use App\Exceptions\PaymentGatewayError;
use App\Http\Controllers\Controller;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\PriceFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The fake test bank's payment page (staging only): approve or decline. The decision is sent
 * back through the normal signed-callback path, then the payer returns to the site.
 */
class FakeBankController extends Controller
{
    public function show(string $order, PaymentGatewayManager $gateways, PriceFormatter $price): View
    {
        try {
            $data = $this->bank($gateways)->order($order);
        } catch (PaymentGatewayError) {
            abort(404);
        }

        return view('pages.billing.fake-bank', [
            'order' => $order,
            'amount' => $price->format((float) $data['amount'], (string) $data['currency']),
            'description' => (string) $data['description'],
            'decided' => $data['status'] !== 'created',
        ]);
    }

    public function decide(Request $request, string $order, PaymentGatewayManager $gateways, HandlePaymentCallback $handle): RedirectResponse
    {
        try {
            $result = $this->bank($gateways)->decide($order, $request->boolean('approve'));
        } catch (PaymentGatewayError) {
            abort(404);
        }

        $callback = Request::create(route('webhooks.payments', ['gateway' => 'fake']), 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_'.str_replace('-', '_', strtoupper(FakeGateway::SIGNATURE_HEADER)) => $result['signature'],
            'REMOTE_ADDR' => '127.0.0.1',
        ], content: $result['body']);

        $handle->handle(GatewayName::Fake, $callback);

        return redirect()->away($result['return_url']);
    }

    private function bank(PaymentGatewayManager $gateways): FakeGateway
    {
        abort_unless($gateways->isEnabled(GatewayName::Fake), 404);

        $gateway = $gateways->for(GatewayName::Fake);

        abort_unless($gateway instanceof FakeGateway, 404);

        return $gateway;
    }
}
