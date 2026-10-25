<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Paid products sold in Phase 8. Prices live in price_plans and are edited in the admin.
 */
enum PricePlanProduct: string implements FilamentLabel
{
    use HasLabel;

    case Vip = 'vip';
    case SuperVip = 'super_vip';
    case Bump = 'bump';
    case AutoBump = 'auto_bump';
    case BusinessPackage = 'business_package';
    case FeaturedHotel = 'featured_hotel';
    case Banner = 'banner';
}
