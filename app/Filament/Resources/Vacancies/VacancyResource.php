<?php

namespace App\Filament\Resources\Vacancies;

use App\Enums\ApplyMethod;
use App\Enums\CategoryModule;
use App\Enums\Currency;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\SalaryPeriod;
use App\Enums\VacancyBenefit;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Resources\ListingResource;
use App\Filament\Resources\Vacancies\RelationManagers\ApplicationsRelationManager;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\Vacancy;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VacancyResource extends ListingResource
{
    protected static ?string $model = Vacancy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 40;

    protected static function langKey(): string
    {
        return 'vacancies';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs([
                'title' => fn (string $name) => TextInput::make($name)->maxLength(160),
                'description' => fn (string $name) => Textarea::make($name)->rows(6),
            ], required: ['title']),
            Section::make(__('admin.sections.details'))
                ->schema([
                    ClassifiedResource::categorySelect(CategoryModule::Vacancies),
                    Select::make('employment_type')->options(EmploymentType::class)->required(),
                    Select::make('experience')->options(Experience::class),
                    DatePicker::make('deadline'),
                    TextInput::make('salary_min')->numeric()->minValue(0),
                    TextInput::make('salary_max')->numeric()->minValue(0)->gte('salary_min'),
                    Select::make('salary_currency')->options(Currency::class)->default(Currency::GEL->value),
                    Select::make('salary_period')->options(SalaryPeriod::class),
                    TagsInput::make('languages')->suggestions(['ka', 'en', 'ru', 'tr', 'de'])->columnSpan(2),
                    CheckboxList::make('benefits')->options(VacancyBenefit::class)->columns(3)->columnSpan(2),
                ])
                ->columns(4),
            Section::make(__('admin.sections.apply'))
                ->schema([
                    Select::make('apply_method')->options(ApplyMethod::class)->required(),
                    TextInput::make('apply_phone')->tel(),
                    TextInput::make('apply_email')->email(),
                ])
                ->columns(3),
            ListingAdmin::ownerSection(),
            ListingAdmin::locationSection(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ListingAdmin::table($table, [
            TextColumn::make('employment_type')->badge()->color('gray'),
            TextColumn::make('salary_min')->label(__('admin.fields.salary'))
                ->formatStateUsing(fn ($state, Vacancy $record): string => trim(($record->salary_min ?? '').'–'.($record->salary_max ?? ''), '–').' '.$record->salary_currency->value),
            TextColumn::make('deadline')->date('d.m.Y')->sortable()->toggleable(),
            TextColumn::make('applications_count')->counts('applications')->sortable(),
        ], [
            SelectFilter::make('employment_type')->options(EmploymentType::class),
        ]);
    }

    protected static function detailEntries(): array
    {
        return [
            Section::make()
                ->schema([
                    TextEntry::make('title')->getStateUsing(fn (Vacancy $record): string => Translatable::of($record, 'title'))->columnSpanFull(),
                    TextEntry::make('employment_type')->badge(),
                    TextEntry::make('experience')->badge()->placeholder('—'),
                    TextEntry::make('salary_min')->placeholder('—'),
                    TextEntry::make('salary_max')->placeholder('—'),
                    TextEntry::make('deadline')->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('apply_method')->badge(),
                    TextEntry::make('user.name')->label(__('admin.fields.owner')),
                    TextEntry::make('description')->getStateUsing(fn (Vacancy $record): string => Translatable::of($record, 'description'))->columnSpanFull()->prose(),
                ])
                ->columns(4),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacancies::route('/'),
            'create' => Pages\CreateVacancy::route('/create'),
            'view' => Pages\ViewVacancy::route('/{record}'),
            'edit' => Pages\EditVacancy::route('/{record}/edit'),
        ];
    }
}
