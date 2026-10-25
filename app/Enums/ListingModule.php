<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Modules a visitor can search and save searches for.
 */
enum ListingModule: string implements FilamentLabel
{
    use HasLabel;

    case Hotels = 'hotels';
    case Properties = 'properties';
    case Classifieds = 'classifieds';
    case Vacancies = 'vacancies';
}
