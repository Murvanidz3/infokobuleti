<?php

namespace App\Contracts;

use App\Enums\Currency;

interface ExchangeRates
{
    /**
     * Lari per one unit of the currency (1.0 for GEL).
     */
    public function rate(Currency $currency): float;

    public function toGel(float $amount, Currency $currency): float;
}
