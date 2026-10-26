<?php

namespace App\Filament\Resources\BusinessProfiles;

use App\Enums\BusinessType;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\BusinessProfile;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BusinessProfileResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = BusinessProfile::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Users;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageUsers;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.business_profiles.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.business_profiles.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    Select::make('user_id')
                        ->label(__('admin.fields.owner'))
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->name ?? (string) $user->phone)
                        ->searchable(['name', 'phone', 'email'])
                        ->required(),
                    Select::make('type')->options(BusinessType::class)->required(),
                    TextInput::make('phone')->tel(),
                    TextInput::make('email')->email(),
                    TextInput::make('website')->url(),
                    KeyValue::make('socials')->columnSpanFull(),
                ])
                ->columns(3),
            Translatable::tabs(['description' => fn (string $name) => Textarea::make($name)->rows(5)]),
            ListingAdmin::locationSection(),
        ])->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('user.name')->label(__('admin.fields.owner')),
                    IconEntry::make('verified_at')->label(__('admin.fields.verified'))->boolean()->getStateUsing(fn (BusinessProfile $record): bool => $record->isVerified()),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('website')->placeholder('—'),
                    TextEntry::make('rating_avg')->placeholder('—'),
                    TextEntry::make('description')->getStateUsing(fn (BusinessProfile $record): string => Translatable::of($record, 'description'))->columnSpanFull(),
                ])
                ->columns(4),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('user.name')->label(__('admin.fields.owner'))->searchable(),
                IconColumn::make('verified_at')->label(__('admin.fields.verified'))->boolean()->getStateUsing(fn (BusinessProfile $record): bool => $record->isVerified()),
                TextColumn::make('rating_avg')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(BusinessType::class),
                TernaryFilter::make('verified_at')->label(__('admin.fields.verified'))->nullable(),
            ])
            ->recordActions([
                self::verifyAction(),
                ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function verifyAction(): Action
    {
        return Action::make('toggleVerified')
            ->label(fn (BusinessProfile $record): string => $record->verified_at !== null ? __('admin.business.unverify') : __('admin.business.verify'))
            ->icon(fn (BusinessProfile $record) => $record->verified_at !== null ? Heroicon::OutlinedXMark : Heroicon::OutlinedCheckBadge)
            ->color(fn (BusinessProfile $record): string => $record->verified_at !== null ? 'gray' : 'success')
            ->requiresConfirmation()
            ->action(function (BusinessProfile $record): void {
                $record->forceFill(['verified_at' => $record->verified_at !== null ? null : now()])->save();

                Notification::make()->success()->title(__('admin.saved'))->send();
            });
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessProfiles::route('/'),
            'create' => Pages\CreateBusinessProfile::route('/create'),
            'view' => Pages\ViewBusinessProfile::route('/{record}'),
            'edit' => Pages\EditBusinessProfile::route('/{record}/edit'),
        ];
    }
}
