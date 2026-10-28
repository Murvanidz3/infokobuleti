<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentGateway as GatewayName;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Resolves card-payment drivers by name (config/payments.php). The fake test bank is never
 * offered in production.
 *
 * @method PaymentGateway driver(string|null $driver = null)
 */
class PaymentGatewayManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->enabled()[0]->value ?? GatewayName::Fake->value;
    }

    public function for(GatewayName $gateway): PaymentGateway
    {
        return $this->driver($gateway->value);
    }

    /**
     * Gateways offered at checkout, in the configured order.
     *
     * @return list<GatewayName>
     */
    public function enabled(): array
    {
        $names = (array) $this->config->get('payments.gateways', []);

        return array_values(array_filter(
            array_map(fn (mixed $name): ?GatewayName => GatewayName::tryFrom((string) $name), $names),
            fn (?GatewayName $gateway): bool => $gateway !== null && ! ($gateway === GatewayName::Fake && $this->container->isProduction()),
        ));
    }

    public function isEnabled(GatewayName $gateway): bool
    {
        return in_array($gateway, $this->enabled(), true);
    }

    protected function createFakeDriver(): PaymentGateway
    {
        if ($this->container->isProduction()) {
            throw new InvalidArgumentException('The fake payment gateway is not available in production.');
        }

        return new FakeGateway($this->container->make('cache.store'), (string) $this->config->get('payments.drivers.fake.secret'));
    }

    protected function createBogDriver(): PaymentGateway
    {
        return new BogGateway((array) $this->config->get('payments.drivers.bog'), $this->container->make('cache.store'));
    }

    protected function createTbcDriver(): PaymentGateway
    {
        return new TbcGateway((array) $this->config->get('payments.drivers.tbc'), $this->container->make('cache.store'));
    }
}
