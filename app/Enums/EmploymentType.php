<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum EmploymentType: string implements FilamentLabel
{
    use HasLabel;

    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Seasonal = 'seasonal';
    case Remote = 'remote';
}
