<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ContactTopic: string implements FilamentLabel
{
    use HasLabel;

    case General = 'general';
    case Listing = 'listing';
    case Advertising = 'advertising';
    case Partnership = 'partnership';
    case Bug = 'bug';
}
