<?php

namespace App\Filament\Resources;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use Filament\Infolists\Components\Entry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Shared behaviour of the listing modules: permission, navigation, record title, trashed records,
 * activity history and the moderation infolist section.
 */
abstract class ListingResource extends Resource
{
    use ChecksPermission;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Listings;

    protected static ?string $recordTitleAttribute = 'slug';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function requiredPermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.'.static::langKey().'.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.'.static::langKey().'.plural');
    }

    abstract protected static function langKey(): string;

    public static function getRecordTitle(?Model $record): string
    {
        return $record === null ? '' : Translatable::of($record, 'title');
    }

    /**
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()->where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    /**
     * Module fields shown in the view page, above the moderation details.
     *
     * @return list<Component|Entry>
     */
    abstract protected static function detailEntries(): array;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ...static::detailEntries(),
            ListingAdmin::moderationSection(),
        ])->columns(1);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }
}
