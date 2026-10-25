<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum CategoryModule: string implements FilamentLabel
{
    use HasLabel;

    case Classifieds = 'classifieds';
    case Vacancies = 'vacancies';
    case News = 'news';
    case Events = 'events';
}
