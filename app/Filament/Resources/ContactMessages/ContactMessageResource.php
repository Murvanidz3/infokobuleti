<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\ContactTopic;
use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Messages from the public contact form.
 */
class ContactMessageResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = ContactMessage::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Moderation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 50;

    public static function requiredPermission(): Permission
    {
        return Permission::ModerateContent;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.contact_messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.contact_messages.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $new = ContactMessage::query()->whereNull('handled_at')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('topic')->badge(),
            TextEntry::make('created_at')->dateTime('d.m.Y H:i'),
            TextEntry::make('name'),
            TextEntry::make('email')->placeholder('—')->copyable(),
            TextEntry::make('phone')->placeholder('—')->copyable(),
            TextEntry::make('locale'),
            TextEntry::make('message')->columnSpanFull()->prose(),
            TextEntry::make('handler.name')->label(__('admin.fields.handled_by'))->placeholder('—'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('handler'))
            ->columns([
                IconColumn::make('handled_at')->label(__('admin.contact.handled'))->boolean()->getStateUsing(fn (ContactMessage $record): bool => $record->handled_at !== null),
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('topic')->badge()->color('gray'),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('message')->limit(80)->wrap(),
            ])
            ->filters([
                TernaryFilter::make('handled')->label(__('admin.contact.handled'))
                    ->queries(true: fn (Builder $query) => $query->whereNotNull('handled_at'), false: fn (Builder $query) => $query->whereNull('handled_at'))
                    ->default(false),
                SelectFilter::make('topic')->options(ContactTopic::class),
            ])
            ->recordActions([
                Action::make('handle')
                    ->label(__('admin.contact.mark_handled'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (ContactMessage $record): bool => $record->handled_at === null)
                    ->action(fn (ContactMessage $record) => $record->forceFill(['handled_at' => now(), 'handled_by' => auth()->id()])->save()),
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}
