<?php

namespace App\Support\Listings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Public item types visitors can favorite, report, contact or reveal a phone for, by morph alias.
 */
final class ListingTypes
{
    public const array INTERACTIVE = ['property', 'project', 'classified', 'vacancy', 'place', 'event', 'business_profile'];

    /**
     * The published (or otherwise visible) record behind "{type}/{id}", or a 404.
     */
    public static function find(string $type, int $id): Model
    {
        abort_unless(in_array($type, self::INTERACTIVE, true), 404);

        /** @var class-string<Model> $class */
        $class = Relation::getMorphedModel($type);
        $record = $class::query()->findOrFail($id);

        abort_unless(Visibility::canView($record), 404);

        return $record;
    }
}
