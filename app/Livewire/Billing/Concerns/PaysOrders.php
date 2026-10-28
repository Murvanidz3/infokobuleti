<?php

namespace App\Livewire\Billing\Concerns;

use App\Actions\Billing\Checkout;
use App\Enums\PaymentGateway as GatewayName;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientBalance;
use App\Exceptions\PaymentGatewayError;
use App\Exceptions\PurchaseNotAllowed;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use Closure;

/**
 * Shared checkout for the account billing components: payment method and bank choice, then either
 * a redirect to the bank or an instant activation from the balance.
 */
trait PaysOrders
{
    public string $method = 'card';

    public string $gateway = '';

    public function bootPaysOrders(): void
    {
        $enabled = app(PaymentGatewayManager::class)->enabled();

        if (GatewayName::tryFrom($this->gateway) === null || ! in_array(GatewayName::from($this->gateway), $enabled, true)) {
            $this->gateway = $enabled[0]->value ?? '';
        }

        if (PaymentMethod::tryFrom($this->method) === null) {
            $this->method = PaymentMethod::Card->value;
        }
    }

    /**
     * @param  Closure(PaymentMethod): Order  $makeOrder
     */
    protected function pay(Closure $makeOrder, ?PaymentMethod $method = null): void
    {
        $method ??= PaymentMethod::from($this->method);

        try {
            $order = $makeOrder($method);
            $redirect = app(Checkout::class)->handle($order, $method, GatewayName::tryFrom($this->gateway));
        } catch (InsufficientBalance) {
            $this->dispatch('toast', message: __('billing.errors.insufficient_balance'), type: 'error');

            return;
        } catch (PurchaseNotAllowed) {
            $this->dispatch('toast', message: __('billing.errors.not_allowed'), type: 'error');

            return;
        } catch (PaymentGatewayError $e) {
            report($e);
            $this->dispatch('toast', message: __('billing.errors.gateway'), type: 'error');

            return;
        }

        if ($redirect !== null) {
            $this->redirect($redirect);

            return;
        }

        $this->dispatch('toast', message: __('billing.paid_from_balance'), type: 'success');
        $this->dispatch('billing-changed');
    }

    /**
     * @return array<string, string> gateway value => label
     */
    protected function gatewayOptions(): array
    {
        return collect(app(PaymentGatewayManager::class)->enabled())
            ->mapWithKeys(fn (GatewayName $gateway): array => [$gateway->value => $gateway->label()])
            ->all();
    }

    protected function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
