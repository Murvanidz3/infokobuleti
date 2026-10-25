<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Modules a visitor can search and save searches for.
 */
enum ListingModule: string
{
    use HasLabel;

    case Hotels = 'hotels';
    case Properties = 'properties';
    case Classifieds = 'classifieds';
    case Vacancies = 'vacancies';
}
