<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum VacancyBenefit: string
{
    use HasLabel;

    case Accommodation = 'accommodation';
    case Meals = 'meals';
    case Transport = 'transport';
    case Uniform = 'uniform';
    case Training = 'training';
}
