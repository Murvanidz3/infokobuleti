<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CategoryModule: string
{
    use HasLabel;

    case Classifieds = 'classifieds';
    case Vacancies = 'vacancies';
    case News = 'news';
    case Events = 'events';
}
