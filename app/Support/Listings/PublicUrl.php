<?php

namespace App\Support\Listings;

use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Place;
use App\Models\Project;
use App\Models\Property;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;

/**
 * Public page of any item a visitor can save, message about or report.
 */
final class PublicUrl
{
    public static function for(Model $model, ?string $locale = null): ?string
    {
        $route = match (true) {
            $model instanceof Property => 'real-estate.show',
            $model instanceof Project => 'projects.show',
            $model instanceof Classified => 'classifieds.show',
            $model instanceof Vacancy => 'jobs.show',
            $model instanceof Place => 'guide.show',
            $model instanceof Event => 'events.show',
            $model instanceof BusinessProfile => 'business.show',
            default => null,
        };

        return $route === null ? null : localized_route($route, [$model], $locale);
    }
}
