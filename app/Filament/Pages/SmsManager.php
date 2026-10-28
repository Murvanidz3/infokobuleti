<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\SmsFields;
use App\Filament\Widgets\Sms\SmsActivityChart;
use App\Filament\Widgets\Sms\SmsStats;
use App\Services\Settings;
use App\Services\Sms\DeliveryReports;
use App\Services\Sms\ProviderStatus;
use App\Support\GeorgianPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * SMS manager home: balance and delivery figures, the provider and its sender names, quick
 * single send, campaign defaults (opt-out footer, test number). Super admins (manage_sms).
 *
 * @property-read Schema $form
 */
class SmsManager extends Page
{
    use ChecksPermission;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Sms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'sms';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSms;
    }

    public static function getNavigationLabel(): string
    {
        return __('sms.manager.title');
    }

    public function getTitle(): string
    {
        return __('sms.manager.title');
    }

    public function mount(Settings $settings): void
    {
        $this->form->fill([
            'campaign_footer' => $settings->get('sms.campaign_footer'),
            'test_phone' => filled($phone = $settings->get('sms.test_phone')) ? GeorgianPhone::format((string) $phone) : null,
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [SmsStats::class, SmsActivityChart::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            SmsFields::sendAction(),
            Action::make('newCampaign')
                ->label(__('sms.actions.new_campaign'))
                ->icon(Heroicon::OutlinedMegaphone)
                ->color('gray')
                ->url(fn (): string => SmsCampaignResource::getUrl('create')),
            Action::make('refresh')
                ->label(__('sms.actions.refresh'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (ProviderStatus $status, DeliveryReports $reports): void {
                    $status->forget();
                    $changed = $reports->sync();

                    Notification::make()->success()->title(__('sms.notifications.refreshed', ['count' => $changed]))->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Textarea::make('campaign_footer')
                        ->label(__('sms.settings.footer'))
                        ->helperText(fn (Get $get): string => __('sms.settings.footer_help').' '.SmsFields::counter($get('campaign_footer')))
                        ->live(debounce: 400)
                        ->rows(2)
                        ->maxLength(120),
                    SmsFields::phone('test_phone')->label(__('sms.settings.test_phone'))->required(false)->helperText(__('sms.settings.test_phone_help')),
                ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('sms.manager.provider'))
                ->icon(Heroicon::OutlinedSignal)
                ->columns(['md' => 2, 'xl' => 4])
                ->schema([
                    TextEntry::make('driver')->label(__('sms.manager.driver'))->state(fn (): string => (string) config('sms.default'))->badge()
                        ->color(fn (): string => in_array(config('sms.default'), ['log', 'array'], true) ? 'warning' : 'success')
                        ->helperText(fn (): ?string => in_array(config('sms.default'), ['log', 'array'], true) ? __('sms.manager.log_driver') : null),
                    TextEntry::make('sender')->label(__('sms.manager.sender'))->state(fn (): string => (string) config('sms.sender')),
                    TextEntry::make('brands')->label(__('sms.manager.brands'))
                        ->state(fn (ProviderStatus $status): array => array_map(
                            fn (array $brand): string => "{$brand['name']} (#{$brand['id']}) — ".__('sms.brand_status.'.min(2, max(0, $brand['status']))),
                            $status->get()['brands'],
                        ) ?: ['—'])
                        ->listWithLineBreaks(),
                    TextEntry::make('provider_error')->label(__('sms.manager.connection'))
                        ->state(fn (ProviderStatus $status): string => $status->get()['error'] ?? __('sms.manager.connection_ok'))
                        ->color(fn (ProviderStatus $status): string => $status->get()['error'] === null ? 'success' : 'danger'),
                    TextEntry::make('callback')->label(__('sms.manager.callback'))
                        ->state(fn (): string => route('webhooks.sms.ubill'))
                        ->helperText(fn (): string => filled(config('sms.drivers.ubill.callback_key')) ? __('sms.manager.callback_ready') : __('sms.manager.callback_missing'))
                        ->copyable()
                        ->columnSpan(['md' => 2, 'xl' => 4]),
                ]),
            Section::make(__('sms.settings.title'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->schema([
                    Form::make([EmbeddedSchema::make('form')])
                        ->id('form')
                        ->livewireSubmitHandler('save')
                        ->footer([
                            Actions::make([
                                Action::make('save')->label(__('admin.save'))->submit('save'),
                            ]),
                        ]),
                ]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $state = $this->form->getState();

        $settings->put([
            'sms.campaign_footer' => filled($state['campaign_footer'] ?? null) ? trim((string) $state['campaign_footer']) : null,
            'sms.test_phone' => GeorgianPhone::normalize($state['test_phone'] ?? null),
        ]);

        activity('admin')->causedBy(auth()->user())->event('settings_updated')->withProperties(['keys' => ['sms.campaign_footer', 'sms.test_phone']])->log('settings updated');

        Notification::make()->success()->title(__('admin.saved'))->send();
    }
}
