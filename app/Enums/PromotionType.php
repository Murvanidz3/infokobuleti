<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PromotionType: string implements FilamentLabel
{
    use HasLabel;

    case Vip = 'vip';
    case SuperVip = 'super_vip';
    case Bump = 'bump';
    case FeaturedHome = 'featured_home';

    /**
     * Value written to the promotable's promotion_level column; higher sorts first.
     */
    public function level(): int
    {
        return match ($this) {
            self::SuperVip => 2,
            self::Vip => 1,
            self::Bump, self::FeaturedHome => 0,
        };
    }
}
