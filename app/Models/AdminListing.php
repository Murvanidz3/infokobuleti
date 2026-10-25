<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

/**
 * Read-only row of the admin_listings view: one line per listing of any module
 * (hotel, property, classified, vacancy, place, article, event).
 *
 * @property string $key
 * @property string $listing_type
 * @property int $listing_id
 * @property array<string, string>|null $title
 * @property ListingStatus $status
 * @property int|null $user_id
 * @property int|null $risk_score
 * @property int $views_count
 * @property Carbon $created_at
 */
class AdminListing extends Model
{
    protected $table = 'admin_listings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'status' => ListingStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function titleText(): string
    {
        $title = $this->title ?? [];

        return (string) ($title[app()->getLocale()] ?? $title[config('locales.default')] ?? reset($title) ?: '');
    }

    /**
     * The underlying listing model (hotel, property, ...).
     */
    public function listing(): ?Model
    {
        $class = Relation::getMorphedModel($this->listing_type);

        return $class === null ? null : $class::query()->find($this->listing_id);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
