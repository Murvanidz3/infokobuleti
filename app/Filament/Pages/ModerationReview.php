<?php

namespace App\Filament\Pages;

use App\Actions\Moderation\ModerateListing;
use App\Enums\ListingStatus;
use App\Enums\Permission;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Places\PlaceResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\ListingAdmin;
use App\Filament\Support\Translatable;
use App\Models\AdminListing;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * Review pending items one at a time with the keyboard: A approve, R reject, S skip, O open.
 */
class ModerationReview extends Page
{
    use ChecksPermission;

    protected static ?string $slug = 'moderation/review/{key}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.moderation-review';

    private const string SKIPPED_KEY = 'admin.moderation.skipped';

    public string $key;

    public static function requiredPermission(): Permission
    {
        return Permission::ModerateContent;
    }

    public function mount(string $key): void
    {
        $this->key = $key;

        abort_if($this->item() === null, 404);
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.moderation.review');
    }

    public function item(): ?AdminListing
    {
        return AdminListing::query()->with('user')->find($this->key);
    }

    public function listing(): ?Model
    {
        return $this->item()?->listing();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $item = $this->item();
        $listing = $item?->listing();
        $text = fn (string $attribute): string => $listing !== null && array_key_exists($attribute, $listing->getAttributes())
            ? Translatable::of($listing, $attribute)
            : '';

        return [
            'item' => $item,
            'listing' => $listing,
            'description' => $text('description') ?: $text('excerpt') ?: strip_tags($text('body')),
            'photos' => $listing !== null && method_exists($listing, 'getMedia')
                ? $listing->getMedia('gallery')->take(8)->map(fn ($media): string => $media->getUrl('card'))->all()
                : [],
            'owner' => $item?->user,
            'ownerListings' => $item?->user_id ? AdminListing::query()->where('user_id', $item->user_id)->count() : 0,
            'remaining' => ModerationQueue::pendingQuery()->count(),
            'resourceUrl' => $this->resourceUrl(),
        ];
    }

    protected function getHeaderActions(): array
    {
        $pending = fn (): bool => $this->item()?->status === ListingStatus::Pending;

        return [
            Action::make('approve')
                ->label(__('admin.moderation.approve').' (A)')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->keyBindings(['a'])
                ->visible($pending)
                ->action(function (): void {
                    app(ModerateListing::class)->approve($this->listing(), $this->moderator());
                    Notification::make()->success()->title(__('admin.moderation.approved'))->send();
                    $this->goToNext();
                }),
            Action::make('reject')
                ->label(__('admin.moderation.reject').' (R)')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->keyBindings(['r'])
                ->visible($pending)
                ->modalHeading(__('admin.moderation.reject_heading'))
                ->modalSubmitActionLabel(__('admin.moderation.reject'))
                ->schema(ListingAdmin::rejectionForm())
                ->action(function (array $data): void {
                    app(ModerateListing::class)->reject($this->listing(), $this->moderator(), $data['template'] ?? null, $data['note'] ?? null);
                    Notification::make()->success()->title(__('admin.moderation.rejected'))->send();
                    $this->goToNext();
                }),
            Action::make('skip')
                ->label(__('admin.moderation.skip').' (S)')
                ->icon(Heroicon::OutlinedForward)
                ->color('gray')
                ->keyBindings(['s'])
                ->action(function (): void {
                    session()->push(self::SKIPPED_KEY, $this->key);
                    $this->goToNext();
                }),
            Action::make('open')
                ->label(__('admin.moderation.open').' (O)')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->keyBindings(['o'])
                ->visible(fn (): bool => $this->resourceUrl() !== null)
                ->url(fn (): ?string => $this->resourceUrl(), shouldOpenInNewTab: true),
        ];
    }

    private function goToNext(): void
    {
        $skipped = (array) session(self::SKIPPED_KEY, []);
        $next = ModerationQueue::pendingQuery()
            ->whereNotIn('key', [...$skipped, $this->key])
            ->latest('created_at')
            ->value('key');

        if ($next === null) {
            session()->forget(self::SKIPPED_KEY);
            Notification::make()->success()->title(__('admin.moderation.queue_done'))->send();
            $this->redirect(ModerationQueue::getUrl());

            return;
        }

        $this->redirect(self::getUrl(['key' => $next]));
    }

    private function resourceUrl(): ?string
    {
        $item = $this->item();

        if ($item === null) {
            return null;
        }

        $resource = [
            'property' => PropertyResource::class,
            'classified' => ClassifiedResource::class,
            'vacancy' => VacancyResource::class,
            'place' => PlaceResource::class,
            'article' => ArticleResource::class,
            'event' => EventResource::class,
        ][$item->listing_type] ?? null;

        $listing = $item->listing();

        return $resource === null || $listing === null ? null : $resource::getUrl('view', ['record' => $listing]);
    }

    private function moderator(): User
    {
        /** @var User */
        return auth()->user();
    }
}
