<?php

namespace App\Filament\Resources\SmsOptOuts;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\SmsFields;
use App\Models\SmsOptOut;
use App\Support\GeorgianPhone;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Numbers that must not get campaigns (asked to unsubscribe). Sign-in codes still reach them.
 */
class SmsOptOutResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = SmsOptOut::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Sms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'sms/opt-outs';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSms;
    }

    public static function getModelLabel(): string
    {
        return __('sms.opt_outs.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sms.opt_outs.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SmsFields::phone()
                ->formatStateUsing(fn (?string $state): ?string => filled($state) ? GeorgianPhone::format($state) : null)
                ->dehydrateStateUsing(fn (?string $state): ?string => GeorgianPhone::normalize($state))
                ->rule(fn (?SmsOptOut $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    $exists = SmsOptOut::query()->where('phone', GeorgianPhone::normalize(is_string($value) ? $value : null) ?? '')
                        ->when($record !== null, fn (Builder $query) => $query->whereKeyNot($record?->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail(__('sms.errors.already_opted_out'));
                    }
                }),
            TextInput::make('reason')->label(__('sms.fields.reason'))->maxLength(255),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name'))
            ->columns([
                TextColumn::make('phone')->label(__('sms.fields.phone'))->formatStateUsing(fn (string $state): string => GeorgianPhone::format($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('phone', 'like', '%'.substr((string) preg_replace('/\D+/', '', $search), -9).'%')),
                TextColumn::make('reason')->label(__('sms.fields.reason'))->placeholder('—')->limit(60),
                TextColumn::make('creator.name')->label(__('sms.fields.created_by'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('sms.fields.created_at'))->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSmsOptOuts::route('/')];
    }
}
