<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum BannerSlot: string implements FilamentLabel
{
    use HasLabel;

    case HomeTop = 'home_top';
    case HomeMiddle = 'home_middle';
    case ListingSidebar = 'listing_sidebar';
    case ListingResults = 'listing_results';
}
