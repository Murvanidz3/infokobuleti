<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A new listing that matched a saved search; notified_at is set once the owner was told.
 */
#[Fillable(['saved_search_id', 'listing_type', 'listing_id', 'notified_at'])]
class SavedSearchHit extends Model
{
    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $primaryKey = null;

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<SavedSearch, $this>
     */
    public function savedSearch(): BelongsTo
    {
        return $this->belongsTo(SavedSearch::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function listing(): MorphTo
    {
        return $this->morphTo();
    }
}
