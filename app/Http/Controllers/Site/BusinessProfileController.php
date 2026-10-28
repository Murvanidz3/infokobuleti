<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;
use App\Services\ListingStats;
use App\Services\Seo\StructuredData;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\View\View;

/**
 * Public page of a business: contacts, description, every published listing and reviews.
 */
class BusinessProfileController extends Controller
{
    public function __invoke(BusinessProfile $businessProfile, CardPresenter $cards, DetailPresenter $detail, ListingStats $stats, StructuredData $seo): View
    {
        $profile = $businessProfile->load(['location', 'user', 'media']);

        if (auth()->id() !== $profile->user_id) {
            $stats->recordView($profile);
        }

        $sections = array_filter([
            'hotels' => ListingQuery::public($profile->hotels()->getQuery())->with(['media', 'location'])->orderByPromotion()->limit(12)->get()->map(fn (Hotel $item) => $cards->hotel($item)),
            'properties' => ListingQuery::public($profile->properties()->getQuery())->with(['media', 'location'])->orderByPromotion()->limit(12)->get()->map(fn (Property $item) => $cards->property($item)),
            'vacancies' => ListingQuery::public($profile->vacancies()->getQuery())->with(['location', 'businessProfile.media'])->orderByPromotion()->limit(12)->get()->map(fn (Vacancy $item) => $cards->job($item)),
            'places' => ListingQuery::public($profile->places()->getQuery())->with(['media', 'location'])->limit(12)->get()->map(fn (Place $item) => $cards->guide($item)),
        ], fn ($items) => $items->isNotEmpty());

        $logo = $profile->getFirstMediaUrl('logo');

        return view('pages.business.show', [
            'seo' => $seo->for($profile),
            'profile' => $profile,
            'sections' => $sections,
            'reviews' => $detail->reviews($profile),
            'owner' => $detail->owner($profile),
            'logo' => $logo !== '' ? $logo : null,
            'address' => $cards->locationText($profile),
            'description' => $cards->excerpt($profile->description, 160),
            'socials' => collect($profile->socials ?? [])->filter(fn ($url) => is_string($url) && str_starts_with($url, 'http'))->all(),
        ]);
    }
}
