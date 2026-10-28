<?php

namespace App\Filament\Resources\Conversations;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Support\ChecksPermission;
use App\Models\Conversation;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Read-only view of user conversations, for abuse reports only.
 */
class ConversationResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Conversation::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Users;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 30;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageUsers;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.conversations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.conversations.plural');
    }

    public static function participantNames(Conversation $conversation): string
    {
        return $conversation->participants->map(fn ($user): string => $user->name ?? (string) $user->phone)->implode(', ');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('participants')->label(__('admin.fields.participants'))->getStateUsing(fn (Conversation $record): string => self::participantNames($record)),
            TextEntry::make('subject_type')->label(__('admin.fields.subject'))->formatStateUsing(fn ($state, Conversation $record): string => $state ? __('admin.morph.'.$state).': '.ReviewResource::subjectTitle($record->subject) : '—'),
            TextEntry::make('last_message_at')->dateTime('d.m.Y H:i')->placeholder('—'),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['participants', 'subject'])->withCount('messages'))
            ->columns([
                TextColumn::make('participants')->label(__('admin.fields.participants'))->getStateUsing(fn (Conversation $record): string => self::participantNames($record)),
                TextColumn::make('subject_type')->label(__('admin.fields.subject'))->formatStateUsing(fn ($state, Conversation $record): string => $state ? ReviewResource::subjectTitle($record->subject) : '—')->limit(40),
                TextColumn::make('messages_count')->label(__('admin.fields.messages'))->sortable(),
                TextColumn::make('last_message_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('last_message_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\MessagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'view' => Pages\ViewConversation::route('/{record}'),
        ];
    }
}
