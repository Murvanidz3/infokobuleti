<?php

namespace App\Services\Currency;

use App\Contracts\ExchangeRates;
use App\Enums\Currency;

/**
 * Rates from config/currency.php. Used in local, tests and as the fallback until the
 * National Bank of Georgia driver arrives in Phase 6.
 */
class FixedExchangeRates implements ExchangeRates
{
    /**
     * @param  array<string, float|int>  $rates
     */
    public function __construct(private array $rates) {}

    public function rate(Currency $currency): float
    {
        if ($currency === Currency::GEL) {
            return 1.0;
        }

        return (float) ($this->rates[$currency->value] ?? throw new \InvalidArgumentException("No exchange rate for {$currency->value}."));
    }

    public function toGel(float $amount, Currency $currency): float
    {
        return round($amount * $this->rate($currency), 2);
    }
}
