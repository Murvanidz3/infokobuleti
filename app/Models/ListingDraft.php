<?php

namespace App\Models;

use Database\Factories\ListingDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Autosaved add-listing wizard state ("data" holds the form). New photos live on the draft until
 * submission moves them to the listing's gallery.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $listing_type
 * @property int|null $listing_id
 * @property int $step
 * @property array<string, mixed> $data
 */
#[Fillable(['user_id', 'listing_type', 'listing_id', 'step', 'data'])]
class ListingDraft extends Model implements HasMedia
{
    /** @use HasFactory<ListingDraftFactory> */
    use HasFactory, InteractsWithMedia, Prunable;

    /** Drafts nobody touched for this long are removed (with their photos). */
    public const int PRUNE_AFTER_DAYS = 30;

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'step' => 'integer',
            'listing_id' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays(self::PRUNE_AFTER_DAYS));
    }

    public function isEditing(): bool
    {
        return $this->listing_id !== null;
    }
}
