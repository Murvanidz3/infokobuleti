<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Saved items of signed-in users.
 */
class Favorites
{
    /** @var array<string, array<int, bool>> */
    private array $cache = [];

    public function __construct(private ListingStats $stats) {}

    /**
     * Whether the current user saved this record (one query per type per request).
     */
    public function isSaved(Model $record): bool
    {
        return $this->isSavedKey($record->getMorphClass().':'.$record->getKey());
    }

    /**
     * Same as isSaved() for a "type:id" key (cards cached without the visitor's state).
     */
    public function isSavedKey(string $key): bool
    {
        [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
        $user = auth()->user();

        if (! $user instanceof User || $type === null || $id === null) {
            return false;
        }

        $this->cache[$type] ??= Favorite::query()
            ->where('user_id', $user->getKey())
            ->where('favoritable_type', $type)
            ->pluck('favoritable_id')
            ->mapWithKeys(fn ($id): array => [(int) $id => true])
            ->all();

        return isset($this->cache[$type][(int) $id]);
    }

    /**
     * @return bool whether the record is saved after the toggle
     */
    public function toggle(User $user, Model $record): bool
    {
        $attributes = ['user_id' => $user->getKey(), 'favoritable_type' => $record->getMorphClass(), 'favoritable_id' => $record->getKey()];
        $existing = Favorite::query()->where($attributes)->first();

        unset($this->cache[$record->getMorphClass()]);

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        Favorite::query()->create($attributes);
        $this->stats->record($record, 'favorites');

        return true;
    }
}
