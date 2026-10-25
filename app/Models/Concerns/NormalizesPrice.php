<?php

namespace App\Models\Concerns;

use App\Contracts\ExchangeRates;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps price_gel (used for cross-currency filtering and sorting) in sync with price + currency.
 *
 * @property float|null $price
 * @property Currency $currency
 * @property float|null $price_gel
 */
trait NormalizesPrice
{
    public static function bootNormalizesPrice(): void
    {
        static::saving(function (Model $model): void {
            /** @var static $model */
            if ($model->isDirty(['price', 'currency']) || $model->price_gel === null) {
                $model->refreshPriceGel(app(ExchangeRates::class));
            }
        });
    }

    public function initializeNormalizesPrice(): void
    {
        $this->mergeCasts([
            'price' => 'float',
            'currency' => Currency::class,
            'price_gel' => 'float',
        ]);
    }

    public function refreshPriceGel(ExchangeRates $rates): void
    {
        $this->price_gel = $this->price === null
            ? null
            : $rates->toGel($this->price, $this->currency ?? Currency::GEL);
    }
}
