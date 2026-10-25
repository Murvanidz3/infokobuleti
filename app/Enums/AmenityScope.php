<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum AmenityScope: string implements FilamentLabel
{
    use HasLabel;

    case Hotel = 'hotel';
    case Room = 'room';
    case Property = 'property';
}
