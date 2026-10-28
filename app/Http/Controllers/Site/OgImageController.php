<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;
use App\Services\Seo\OgImage;
use App\Services\Seo\StructuredData;
use App\Support\Listings\Visibility;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The share picture of a public page (/og/{type}/{id}-{locale}.jpg), created on the first request.
 */
class OgImageController extends Controller
{
    /** @var array<string, class-string<Model>> */
    public const array TYPES = [
        'hotel' => Hotel::class,
        'property' => Property::class,
        'classified' => Classified::class,
        'vacancy' => Vacancy::class,
        'place' => Place::class,
        'event' => Event::class,
        'article' => Article::class,
        'business_profile' => BusinessProfile::class,
    ];

    public function __invoke(string $type, int $id, string $locale, StructuredData $seo, OgImage $images): BinaryFileResponse
    {
        abort_unless(array_key_exists($locale, config('locales.supported')), 404);
        app()->setLocale($locale);

        $record = self::TYPES[$type]::query()->with('media')->find($id);
        abort_if($record === null || ! Visibility::isLive($record), 404);

        return response()->file($images->path($record, $seo->for($record)['vars']), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
