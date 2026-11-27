<?php

namespace App\Providers\Filament;

use App\Filament\NavigationGroup;
use App\Filament\Pages\Dashboard;
use App\Http\Controllers\Admin\SwitchAdminLocaleController;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\LogoutBlockedUsers;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\TrackUserSessions;
use Filament\Actions\Action;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\Field;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\Entry;
use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The staff admin at /admin. Sign-in happens on the site's staff login (password + authenticator).
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('infokobuleti')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#00C9FF'),
                'gray' => Color::Neutral,
                'success' => Color::hex('#6beba4'),
                'danger' => Color::hex('#f52525'),
                'warning' => Color::hex('#e7ea68'),
                'info' => Color::hex('#00C9FF'),
            ])
            ->darkMode(isForced: true)
            ->themeSwitcher(false)
            ->font(
                'Noto Sans Georgian Variable',
                provider: LocalFontProvider::class,
                preload: fn (): array => $this->fontPreload(),
            )
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Full page loads re-fetch the theme CSS, JS and fonts and flash the shell.
            ->spa(hasPrefetching: true)
            ->spaUrlExceptions([
                '*/logout',
                '*/admin/locale/*',
                '*/admin/impersonate/*',
                '*/impersonation/*',
                '*/account/*',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18.5rem')
            ->maxContentWidth('full')
            ->navigationGroups(collect(NavigationGroup::cases())->mapWithKeys(fn (NavigationGroup $group): array => [
                // Labels are resolved per request, after the admin language is applied.
                $group->name => FilamentNavigationGroup::make()->label(fn (): string => $group->getLabel()),
            ])->all())
            ->databaseNotifications(position: DatabaseNotificationsPosition::Topbar)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->userMenuItems([
                'site' => Action::make('site')
                    ->label(fn (): string => __('admin.menu.open_site'))
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn (): string => localized_route('home'), shouldOpenInNewTab: true),
                'security' => Action::make('security')
                    ->label(fn (): string => __('admin.menu.security'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->url(fn (): string => localized_route('account.security')),
                'language' => Action::make('language')
                    ->label(fn (): string => __('locales.'.(SetAdminLocale::current() === 'ka' ? 'en' : 'ka'), locale: SetAdminLocale::current() === 'ka' ? 'en' : 'ka'))
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn (): string => route('filament.admin.locale', SetAdminLocale::current() === 'ka' ? 'en' : 'ka')),
                // Sign out through the site so the device list stays accurate.
                'logout' => fn (Action $action): Action => $action->url(fn (): string => localized_route('logout')),
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('locale/{locale}', SwitchAdminLocaleController::class)
                    ->whereIn('locale', SetAdminLocale::LOCALES)
                    ->name('locale');
            })
            ->middleware([
                SecurityHeaders::class.':admin',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetAdminLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                LogoutBlockedUsers::class,
                TrackUserSessions::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateAdmin::class,
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => view('filament.hooks.navigation-fade')->render(),
        );

        // Every field, column, entry and filter takes its label from lang/{locale}/admin.php
        // ("fields.<name>"); names without a translation keep Filament's default label.
        $label = static function (string $name): ?string {
            $key = 'admin.fields.'.Str::before($name, '.');

            return Lang::has($key) ? __($key) : null;
        };

        Field::configureUsing(fn (Field $field) => $field->label(fn (): ?string => $label($field->getName())));
        Column::configureUsing(fn (Column $column) => $column->label(fn (): ?string => $label($column->getName())));
        Entry::configureUsing(fn (Entry $entry) => $entry->label(fn (): ?string => $label($entry->getName())));
        BaseFilter::configureUsing(fn (BaseFilter $filter) => $filter->label(fn (): ?string => $label($filter->getName())));
    }

    /**
     * The Georgian, Latin and Cyrillic files the admin actually paints, so the first
     * paint does not swap fonts after the CSS arrives. Vite fingerprints them; the
     * preload URL has to be the same one the stylesheet requests.
     *
     * @return list<string>
     */
    private function fontPreload(): array
    {
        if (Vite::isRunningHot()) {
            return [];
        }

        return array_map(
            static fn (string $file): string => Vite::asset($file),
            [
                'node_modules/@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-georgian-wght-normal.woff2',
                'node_modules/@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-latin-wght-normal.woff2',
                'node_modules/@fontsource-variable/noto-sans/files/noto-sans-cyrillic-wght-normal.woff2',
            ],
        );
    }
}
