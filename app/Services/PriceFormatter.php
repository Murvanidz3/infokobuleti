<?php

namespace App\Services;

use Illuminate\Support\Number;

/**
 * Formats money the way the design shows it: currency symbol first, locale-aware digit grouping
 * ("₾220", "$58 000" in Georgian, "$58,000" in English).
 */
class PriceFormatter
{
    private const SYMBOLS = [
        'GEL' => '₾',
        'USD' => '$',
        'EUR' => '€',
    ];

    public function format(int|float $amount, string $currency = 'GEL', ?string $locale = null): string
    {
        return $this->symbol($currency).$this->number($amount, $locale);
    }

    /**
     * A price range sharing one symbol: "₾1 500–2 000".
     */
    public function range(int|float $from, int|float $to, string $currency = 'GEL', ?string $locale = null): string
    {
        return $this->format($from, $currency, $locale).'–'.$this->number($to, $locale);
    }

    public function symbol(string $currency): string
    {
        return self::SYMBOLS[$currency] ?? $currency.' ';
    }

    public function number(int|float $amount, ?string $locale = null): string
    {
        $formatted = Number::format($amount, maxPrecision: 2, locale: $locale ?? app()->getLocale());

        return $formatted === false ? (string) $amount : $formatted;
    }
}
