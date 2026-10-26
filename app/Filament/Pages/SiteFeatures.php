<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Enums\SiteFeature;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Support\Features;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Switches for the Phase 9 features (Laravel Pennant): turn each one on or off for the whole site.
 */
class SiteFeatures extends Page
{
    use ChecksPermission;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'features';

    protected string $view = 'filament.pages.site-features';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSettings;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.features.title');
    }

    public function getTitle(): string
    {
        return __('admin.features.title');
    }

    public function toggle(string $feature): void
    {
        $case = SiteFeature::tryFrom($feature);

        if ($case === null) {
            return;
        }

        $on = ! Features::active($case);
        Features::set($case, $on);

        activity('admin')->causedBy(auth()->user())->event($on ? 'feature_on' : 'feature_off')->withProperties(['feature' => $case->value])->log('feature switched');

        Notification::make()->success()->title(__($on ? 'admin.features.turned_on' : 'admin.features.turned_off', ['name' => $case->label()]))->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'features' => collect(SiteFeature::cases())->map(fn (SiteFeature $feature): array => [
                'key' => $feature->value,
                'label' => $feature->label(),
                'help' => __('admin.features.help.'.$feature->value),
                'active' => Features::active($feature),
                'needs' => match ($feature) {
                    SiteFeature::TelegramBot => blank(config('services.telegram.bot_token')) ? __('admin.features.needs.telegram') : null,
                    SiteFeature::WebPush => blank(config('webpush.vapid.public_key')) ? __('admin.features.needs.vapid') : null,
                    default => null,
                },
            ])->all(),
        ];
    }
}
