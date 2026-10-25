<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum BedType: string implements FilamentLabel
{
    use HasLabel;

    case Single = 'single';
    case Double = 'double';
    case Twin = 'twin';
    case Sofa = 'sofa';
    case Bunk = 'bunk';
}
