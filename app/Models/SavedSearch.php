<?php

namespace App\Models;

use App\Enums\ListingModule;
use App\Enums\SearchFrequency;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'module', 'name', 'filters', 'channels', 'frequency', 'is_active'])]
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'module' => ListingModule::class,
            'filters' => 'array',
            'channels' => 'array',
            'frequency' => SearchFrequency::class,
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
