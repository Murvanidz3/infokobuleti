<?php

namespace App\Support;

use App\Enums\SiteFeature;
use Laravel\Pennant\Feature;

/**
 * Site-wide feature switches (Laravel Pennant, one global scope).
 */
class Features
{
    public static function active(SiteFeature $feature): bool
    {
        return Feature::active($feature->value);
    }

    public static function set(SiteFeature $feature, bool $on): void
    {
        // The site has one global scope (resolveScopeUsing → null), so this switches it for everyone.
        $on ? Feature::activate($feature->value) : Feature::deactivate($feature->value);
    }

    public static function define(): void
    {
        Feature::resolveScopeUsing(fn () => null);

        foreach (SiteFeature::cases() as $feature) {
            Feature::define($feature->value, fn (): bool => $feature->defaultState());
        }
    }
}
