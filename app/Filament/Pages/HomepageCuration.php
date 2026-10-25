<?php

namespace App\Filament\Pages;

use App\Enums\ListingStatus;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Hotel;
use App\Models\Property;
use App\Services\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use UnitEnum;

/**
 * Hand-picked hotels and properties for the homepage, the hero photo per season and the season
 * override (the homepage reads these in Phase 6; the season logic arrives in Phase 9).
 *
 * @property-read Schema $form
 */
class HomepageCuration extends Page
{
    use ChecksPermission;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'homepage';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.homepage.title');
    }

    public function getTitle(): string
    {
        return __('admin.homepage.title');
    }

    public function mount(Settings $settings): void
    {
        $this->form->fill([
            'featured_hotels' => $settings->get('homepage.featured_hotels', []),
            'featured_properties' => $settings->get('homepage.featured_properties', []),
            'hero_summer' => $settings->get('homepage.hero.summer'),
            'hero_winter' => $settings->get('homepage.hero.winter'),
            'season' => $settings->get('homepage.season', 'auto'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.homepage.featured'))->description(__('admin.homepage.featured_help'))->schema([
                    Select::make('featured_hotels')->label(__('admin.resources.hotels.plural'))
                        ->multiple()->maxItems(12)->searchable()
                        ->options(fn (): array => self::options(Hotel::class)),
                    Select::make('featured_properties')->label(__('admin.resources.properties.plural'))
                        ->multiple()->maxItems(12)->searchable()
                        ->options(fn (): array => self::options(Property::class)),
                ]),
                Section::make(__('admin.homepage.hero'))->schema([
                    ToggleButtons::make('season')->label(__('admin.homepage.season'))->inline()->options([
                        'auto' => __('admin.homepage.seasons.auto'),
                        'summer' => __('admin.homepage.seasons.summer'),
                        'winter' => __('admin.homepage.seasons.winter'),
                    ])->default('auto'),
                    Grid::make(2)->schema([
                        FileUpload::make('hero_summer')->label(__('admin.homepage.hero_summer'))->image()->disk('public')->directory('hero')->maxSize(6144)->imageEditor(),
                        FileUpload::make('hero_winter')->label(__('admin.homepage.hero_winter'))->image()->disk('public')->directory('hero')->maxSize(6144)->imageEditor(),
                    ]),
                ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label(__('admin.save'))->submit('save')->keyBindings(['mod+s'])])]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $state = $this->form->getState();

        $settings->put([
            'homepage.featured_hotels' => array_map('intval', $state['featured_hotels'] ?? []),
            'homepage.featured_properties' => array_map('intval', $state['featured_properties'] ?? []),
            'homepage.hero.summer' => Arr::first(Arr::wrap($state['hero_summer'] ?? null)),
            'homepage.hero.winter' => Arr::first(Arr::wrap($state['hero_winter'] ?? null)),
            'homepage.season' => $state['season'] ?? 'auto',
        ]);

        activity('admin')->causedBy(auth()->user())->event('homepage_updated')->log('homepage curation updated');

        Notification::make()->success()->title(__('admin.saved'))->send();
    }

    /**
     * Published listings as select options, newest first.
     *
     * @param  class-string<Hotel|Property>  $model
     * @return array<int, string>
     */
    private static function options(string $model): array
    {
        return $model::query()
            ->where('status', ListingStatus::Published)
            ->latest('published_at')
            ->limit(300)
            ->get(['id', 'title'])
            ->mapWithKeys(fn ($record): array => [$record->id => Translatable::of($record, 'title')])
            ->all();
    }
}
