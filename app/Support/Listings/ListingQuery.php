<?php

namespace App\Support\Listings;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The one definition of "visible to the public": published and not past its expiry date.
 * Every public list, count, map and search goes through here so hidden statuses never leak.
 */
final class ListingQuery
{
    /** Morph aliases of modules whose items expire (tables with an expires_at column). */
    public const array EXPIRING = ['property', 'project', 'classified', 'vacancy'];

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function public(Builder $query): Builder
    {
        $model = $query->getModel();

        $query->where($model->qualifyColumn('status'), 'published');

        if (in_array($model->getMorphClass(), self::EXPIRING, true)) {
            $query->where(fn (Builder $query) => $query
                ->whereNull($model->qualifyColumn('expires_at'))
                ->orWhere($model->qualifyColumn('expires_at'), '>', now()));
        }

        return $query;
    }
}
