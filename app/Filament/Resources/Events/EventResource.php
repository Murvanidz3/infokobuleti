<?php

namespace App\Filament\Resources\Events;

use App\Enums\CategoryModule;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Resources\ListingResource;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Event;
use App\Models\Place;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EventResource extends ListingResource
{
    protected static ?string $model = Event::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 30;

    protected static function langKey(): string
    {
        return 'events';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'title' => fn (string $name) => TextInput::make($name)->maxLength(160),
                'description' => fn (string $name) => Textarea::make($name)->rows(6),
                'venue' => fn (string $name) => TextInput::make($name)->maxLength(160),
            ], required: ['title']),
            Section::make(__('admin.sections.details'))
                ->schema([
                    DateTimePicker::make('starts_at')->seconds(false)->required(),
                    DateTimePicker::make('ends_at')->seconds(false)->after('starts_at'),
                    Toggle::make('is_all_day')->inline(false),
                    ClassifiedResource::categorySelect(CategoryModule::Events),
                    Select::make('place_id')
                        ->relationship('place', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Place $place): string => Translatable::of($place, 'title'))
                        ->searchable(),
                    TextInput::make('organizer')->maxLength(160),
                    Toggle::make('is_free')->inline(false)->live(),
                    TextInput::make('price_from')->numeric()->minValue(0)->prefix('₾')->hidden(fn (callable $get): bool => (bool) $get('is_free')),
                    TextInput::make('ticket_url')->url()->columnSpan(2),
                ])
                ->columns(4),
            ListingAdmin::ownerSection(),
            ListingAdmin::locationSection(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ListingAdmin::table($table, [
            TextColumn::make('starts_at')->dateTime('d.m.Y H:i')->sortable(),
        ], [
            Filter::make('upcoming')->label(__('admin.filters.upcoming'))->query(fn (Builder $query): Builder => $query->where('starts_at', '>=', now()))->default(),
        ])->defaultSort('starts_at');
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Event $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('starts_at')->dateTime('d.m.Y H:i'),
                    TextEntry::make('ends_at')->dateTime('d.m.Y H:i')->placeholder('—'),
                    TextEntry::make('organizer')->placeholder('—'),
                    TextEntry::make('ticket_url')->placeholder('—'),
                    TextEntry::make('description')->getStateUsing(fn (Event $record): string => Translatable::of($record, 'description'))->columnSpanFull()->prose(),
                ])
                ->columns(4),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
