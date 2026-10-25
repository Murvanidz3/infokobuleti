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

    /**
     * Short form for map pins: "₾95", "$58K", "₾1.2M".
     */
    public function compact(int|float $amount, string $currency = 'GEL'): string
    {
        $value = match (true) {
            $amount >= 1_000_000 => $this->number(round($amount / 1_000_000, 1), 'en').'M',
            $amount >= 10_000 => $this->number(round($amount / 1_000), 'en').'K',
            default => $this->number(round($amount), 'en'),
        };

        return $this->symbol($currency).$value;
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
