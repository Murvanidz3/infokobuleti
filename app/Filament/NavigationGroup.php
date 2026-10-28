<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * Admin sidebar groups, in display order.
 */
enum NavigationGroup: string implements HasLabel
{
    case Moderation = 'moderation';
    case Listings = 'listings';
    case Content = 'content';
    case Users = 'users';
    case Catalog = 'catalog';
    case Finance = 'finance';
    case Sms = 'sms';
    case System = 'system';

    public function getLabel(): string
    {
        return __('admin.nav.'.$this->value);
    }
}
