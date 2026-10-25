<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\Translatable;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReviewResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Review::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Moderation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 20;

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
        return __('admin.resources.reviews.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.reviews.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->where('status', ReviewStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function subjectTitle(?Model $subject): string
    {
        if ($subject === null) {
            return '—';
        }

        return method_exists($subject, 'getTranslations') && in_array('title', $subject->translatable ?? [], true)
            ? Translatable::of($subject, 'title')
            : (string) ($subject->getAttribute('name') ?? $subject->getKey());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('reviewable')->label(__('admin.fields.subject'))->getStateUsing(fn (Review $record): string => self::subjectTitle($record->reviewable)),
            TextEntry::make('user.name')->label(__('admin.fields.author')),
            TextEntry::make('rating')->suffix(' / 10'),
            TextEntry::make('status')->badge(),
            TextEntry::make('body')->columnSpanFull(),
            TextEntry::make('owner_reply')->placeholder('—')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'reviewable']))
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('reviewable_type')->label(__('admin.fields.subject'))
                    ->formatStateUsing(fn ($state, Review $record): string => self::subjectTitle($record->reviewable))->limit(40),
                TextColumn::make('user.name')->label(__('admin.fields.author'))->searchable(),
                TextColumn::make('rating')->badge()->color(fn (int $state): string => $state >= 7 ? 'success' : ($state >= 4 ? 'warning' : 'danger'))->sortable(),
                TextColumn::make('body')->limit(70)->wrap()->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ReviewStatus::class)->default(ReviewStatus::Pending->value),
            ])
            ->recordActions([
                self::statusAction('publish', ReviewStatus::Published, Heroicon::OutlinedCheckCircle, 'success'),
                self::statusAction('reject', ReviewStatus::Rejected, Heroicon::OutlinedXCircle, 'danger'),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('publishSelected')
                    ->label(__('admin.moderation.approve_selected'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        foreach ($records as $review) {
                            if ($review instanceof Review) {
                                self::moderate($review, ReviewStatus::Published);
                            }
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function statusAction(string $name, ReviewStatus $status, Heroicon $icon, string $color): Action
    {
        return Action::make($name)
            ->label(__('admin.reviews.'.$name))
            ->icon($icon)
            ->color($color)
            ->visible(fn (Review $record): bool => $record->status !== $status)
            ->action(function (Review $record) use ($status): void {
                self::moderate($record, $status);

                Notification::make()->success()->title(__('admin.saved'))->send();
            });
    }

    private static function moderate(Review $review, ReviewStatus $status): void
    {
        $review->forceFill(['status' => $status, 'moderated_by' => auth()->id(), 'moderated_at' => now()])->save();
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReviews::route('/')];
    }
}
