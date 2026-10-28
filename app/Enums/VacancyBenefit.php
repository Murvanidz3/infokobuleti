<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum VacancyBenefit: string implements FilamentLabel
{
    use HasLabel;

    case Accommodation = 'accommodation';
    case Meals = 'meals';
    case Transport = 'transport';
    case Uniform = 'uniform';
    case Training = 'training';
}
