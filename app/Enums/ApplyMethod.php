<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ApplyMethod: string implements FilamentLabel
{
    use HasLabel;

    case Site = 'site';
    case Phone = 'phone';
    case Email = 'email';
}
