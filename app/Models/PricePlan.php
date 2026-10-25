<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ListingModule;
use App\Enums\PricePlanProduct;
use App\Models\Concerns\LogsStaffActivity;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * One sellable option: "VIP, 7 days, 15 GEL". Used by the payments flow in Phase 8.
 */
#[Guarded(['id'])]
class PricePlan extends Model
{
    use HasTranslations, LogsStaffActivity;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'product' => PricePlanProduct::class,
            'module' => ListingModule::class,
            'currency' => Currency::class,
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
