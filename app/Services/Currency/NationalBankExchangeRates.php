<?php

namespace App\Services\Currency;

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use App\Services\Info\UsefulInfo;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Today's National Bank of Georgia rates (refreshed by php artisan info:refresh), falling back to
 * the fixed rates in config/currency.php when none have been fetched yet.
 */
class NationalBankExchangeRates implements ExchangeRates
{
    public function __construct(
        private Cache $cache,
        private FixedExchangeRates $fallback,
    ) {}

    public function rate(Currency $currency): float
    {
        if ($currency === Currency::GEL) {
            return 1.0;
        }

        $rates = $this->cache->get(UsefulInfo::RATES_KEY);
        $rate = is_array($rates) ? ($rates['rates'][$currency->value] ?? null) : null;

        return is_numeric($rate) && $rate > 0 ? (float) $rate : $this->fallback->rate($currency);
    }

    public function toGel(float $amount, Currency $currency): float
    {
        return round($amount * $this->rate($currency), 2);
    }
}
