<?php

namespace App\Filament\Pages;

use App\Contracts\HumanVerifier;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Services\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use UnitEnum;

/**
 * Contacts, social links, SEO defaults, listing rules, integration status and maintenance mode.
 * Only super admins (manage_settings).
 *
 * @property-read Schema $form
 */
class SiteSettings extends Page
{
    use ChecksPermission;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'settings';

    /** Modules with an expiry, keyed by morph alias. */
    public const array EXPIRING_MODULES = ['hotel', 'property', 'classified', 'vacancy'];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSettings;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public function mount(Settings $settings): void
    {
        $values = [];

        foreach ($settings->all() as $key => $value) {
            Arr::set($values, $key, $value);
        }

        foreach (self::EXPIRING_MODULES as $module) {
            $values['listings']['expiry_days'][$module] ??= config("listings.expiry_days.{$module}");
        }

        $values['contacts']['phone'] ??= config('site.contact.phone');
        $values['contacts']['email'] ??= config('site.contact.email');

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('settings')->tabs([
                    Tab::make(__('admin.settings.tabs.contacts'))->icon(Heroicon::OutlinedPhone)->schema([
                        Grid::make(2)->schema([
                            TextInput::make('contacts.phone')->label(__('admin.fields.phone'))->tel(),
                            TextInput::make('contacts.email')->label(__('admin.fields.email'))->email(),
                            TextInput::make('contacts.whatsapp')->label('WhatsApp')->tel(),
                            TextInput::make('contacts.address')->label(__('admin.fields.address')),
                        ]),
                        Section::make(__('admin.settings.social'))->schema([
                            Grid::make(2)->schema(array_map(
                                fn (string $network) => TextInput::make("social.{$network}")->label(ucfirst($network))->url(),
                                ['facebook', 'instagram', 'youtube', 'telegram', 'tiktok'],
                            )),
                        ]),
                    ]),
                    Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                        Translatable::tabs([
                            'seo.title' => fn (string $name) => TextInput::make($name)->maxLength(70),
                            'seo.description' => fn (string $name) => Textarea::make($name)->rows(3)->maxLength(170),
                        ]),
                    ]),
                    Tab::make(__('admin.settings.tabs.listings'))->icon(Heroicon::OutlinedClock)->schema([
                        Section::make(__('admin.settings.expiry_days'))->description(__('admin.settings.expiry_help'))->schema([
                            Grid::make(4)->schema(array_map(
                                fn (string $module) => TextInput::make("listings.expiry_days.{$module}")->label(__('admin.morph.'.$module))->numeric()->minValue(1)->maxValue(3650)->suffix(__('admin.price_plans.days')),
                                self::EXPIRING_MODULES,
                            )),
                        ]),
                        Section::make(__('admin.settings.free_limits'))->description(__('admin.settings.free_limits_help'))->schema([
                            Grid::make(2)->schema([
                                TextInput::make('listings.free_limit.individual')->label(__('enums.account_type.individual'))->numeric()->minValue(0),
                                TextInput::make('listings.free_limit.business')->label(__('enums.account_type.business'))->numeric()->minValue(0),
                            ]),
                        ]),
                    ]),
                    Tab::make(__('admin.settings.tabs.integrations'))->icon(Heroicon::OutlinedPuzzlePiece)->schema([
                        Grid::make(2)->schema(self::integrationEntries()),
                    ]),
                    Tab::make(__('admin.settings.tabs.maintenance'))->icon(Heroicon::OutlinedWrenchScrewdriver)->schema([
                        Toggle::make('site.maintenance')->label(__('admin.settings.maintenance'))->helperText(__('admin.settings.maintenance_help')),
                        Translatable::tabs(['site.maintenance_message' => fn (string $name) => Textarea::make($name)->rows(2)]),
                    ]),
                ])->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('admin.save'))->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $state = $this->form->getState();
        $flat = Arr::dot($state);

        $settings->put($flat);

        activity('admin')->causedBy(auth()->user())->event('settings_updated')->withProperties(['keys' => array_keys($flat)])->log('settings updated');

        Notification::make()->success()->title(__('admin.saved'))->send();
    }

    /**
     * Read-only status of every external service, keys masked.
     *
     * @return list<TextEntry>
     */
    private static function integrationEntries(): array
    {
        $mask = fn (?string $value): string => blank($value) ? __('admin.settings.not_configured') : substr($value, 0, 4).str_repeat('•', 6).substr($value, -4);

        return [
            TextEntry::make('sms')->label(__('admin.settings.integration.sms'))
                ->state(fn (): string => config('sms.default').(config('sms.default') === 'twilio' ? ' · '.$mask(config('sms.drivers.twilio.account_sid')) : '')),
            TextEntry::make('google')->label(__('admin.settings.integration.google'))
                ->state(fn (): string => $mask(config('services.google.client_id'))),
            TextEntry::make('turnstile')->label('Cloudflare Turnstile')
                ->state(fn (): string => app(HumanVerifier::class)->enabled() ? $mask(config('services.turnstile.site_key')) : __('admin.settings.not_configured')),
            TextEntry::make('mail')->label(__('admin.settings.integration.mail'))->state(fn (): string => (string) config('mail.default')),
            TextEntry::make('payments')->label(__('admin.settings.integration.payments'))->state(fn (): string => __('admin.settings.coming_phase', ['phase' => 8])),
            TextEntry::make('ai')->label(__('admin.settings.integration.ai'))->state(fn (): string => __('admin.settings.coming_phase', ['phase' => 9])),
            TextEntry::make('search')->label(__('admin.settings.integration.search'))->state(fn (): string => (string) config('scout.driver')),
            TextEntry::make('queue')->label(__('admin.settings.integration.queue'))->state(fn (): string => (string) config('queue.default')),
        ];
    }
}
