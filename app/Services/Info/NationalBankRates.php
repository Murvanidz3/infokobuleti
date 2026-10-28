<?php

namespace App\Services\Info;

use Illuminate\Support\Facades\Http;

/**
 * Official GEL exchange rates published daily by the National Bank of Georgia.
 */
class NationalBankRates
{
    /**
     * @param  list<string>  $currencies
     * @return array{date: string, rates: array<string, float>} GEL per one unit of each currency
     *
     * @throws \Throwable when the API is unreachable or the answer is malformed
     */
    public function fetch(array $currencies): array
    {
        $response = Http::timeout(8)->retry(2, 300)
            ->get(config('info.rates.url').'?'.implode('&', array_map(fn (string $code): string => 'currencies='.urlencode($code), $currencies)))
            ->throw()
            ->json();

        $day = $response[0] ?? null;
        $rates = [];

        foreach ($day['currencies'] ?? [] as $currency) {
            $quantity = max(1, (int) ($currency['quantity'] ?? 1));

            if (isset($currency['code'], $currency['rate']) && is_numeric($currency['rate'])) {
                $rates[(string) $currency['code']] = round((float) $currency['rate'] / $quantity, 6);
            }
        }

        if ($rates === []) {
            throw new \UnexpectedValueException('The National Bank of Georgia returned no rates.');
        }

        return ['date' => (string) ($day['date'] ?? now()->toDateString()), 'rates' => $rates];
    }
}
