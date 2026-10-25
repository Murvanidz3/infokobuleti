<?php

namespace App\Models\Concerns;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Favoritable
{
    /**
     * @return MorphMany<Favorite, $this>
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function isFavoritedBy(?User $user): bool
    {
        return $user !== null && $this->favorites()->whereBelongsTo($user)->exists();
    }
}
