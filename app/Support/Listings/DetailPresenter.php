<?php

namespace App\Support\Listings;

use App\Enums\ReviewStatus;
use App\Enums\SiteFeature;
use App\Models\BusinessProfile;
use App\Models\Place;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use App\Services\DateFormatter;
use App\Support\Features;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Pieces shared by the detail pages: gallery, owner card, nearby places and reviews.
 */
class DetailPresenter
{
    public function __construct(
        private CardPresenter $cards,
        private DateFormatter $dates,
    ) {}

    /**
     * @return list<array{thumb: string, large: string, placeholder: ?string}>
     */
    public function gallery(HasMedia $model): array
    {
        return $model->getMedia('gallery')
            ->map(fn (Media $media): array => [
                'thumb' => $media->getAvailableUrl(['card', 'thumb']) ?: $media->getUrl(),
                'large' => $media->getAvailableUrl(['large', 'card']) ?: $media->getUrl(),
                'placeholder' => $media->hasGeneratedConversion('placeholder') ? $media->getUrl('placeholder') : null,
            ])
            ->values()
            ->all();
    }

    /**
     * The "who you deal with" card: business profile when there is one, else the private owner.
     *
     * @return array{name: string, kind: string, verified: bool, phoneVerified: bool, ownerVerified: bool, since: ?string, url: ?string, initials: string, rating: ?string}
     */
    public function owner(Model $record): array
    {
        $profile = match (true) {
            $record instanceof BusinessProfile => $record,
            $record->getAttribute('business_profile_id') !== null => $record->getRelationValue('businessProfile'),
            default => null,
        };
        $owner = Contact::owner($record);
        $ownerVerified = $record instanceof Property && $record->owner_verified_at !== null && Features::active(SiteFeature::TrustLayer);

        if ($profile instanceof BusinessProfile) {
            return [
                'name' => $profile->name,
                'kind' => (string) $profile->type->getLabel(),
                'verified' => $profile->isVerified(),
                'phoneVerified' => (bool) $owner?->hasVerifiedPhone(),
                'ownerVerified' => $ownerVerified,
                'since' => $profile->created_at ? __('site.owner.since', ['year' => $profile->created_at->year]) : null,
                'url' => localized_route('business.show', [$profile]),
                'initials' => mb_strtoupper(mb_substr($profile->name, 0, 1)),
                'rating' => $profile->rating_avg !== null ? number_format((float) $profile->rating_avg, 1) : null,
            ];
        }

        return [
            'name' => $owner?->name ?: (string) ($record->getAttribute('contact_name') ?: __('site.owner.private')),
            'kind' => __('site.owner.private'),
            'verified' => false,
            'phoneVerified' => (bool) $owner?->hasVerifiedPhone(),
            'ownerVerified' => $ownerVerified,
            'since' => $owner?->created_at ? __('site.owner.since', ['year' => $owner->created_at->year]) : null,
            'url' => null,
            'initials' => $owner instanceof User ? $owner->initials() : '•',
            'rating' => null,
        ];
    }

    /**
     * Published places within ~1.5 km, closest first, as map pins.
     *
     * @return list<array{lat: float, lng: float, pin: null, title: string, url: string, image: ?string, meta: string}>
     */
    public function nearby(Model $record, int $limit = 6): array
    {
        $lat = $record->getAttribute('lat');
        $lng = $record->getAttribute('lng');

        if ($lat === null || $lng === null) {
            return [];
        }

        $delta = 0.014;

        return ListingQuery::public(Place::query())
            ->with(['media', 'location'])
            ->when($record instanceof Place, fn ($query) => $query->whereKeyNot($record->getKey()))
            ->withinBounds((float) $lat - $delta, (float) $lng - $delta * 1.35, (float) $lat + $delta, (float) $lng + $delta * 1.35)
            ->nearest((float) $lat, (float) $lng)
            ->limit($limit)
            ->get()
            ->map(fn (Place $place): array => [
                'lat' => (float) $place->lat,
                'lng' => (float) $place->lng,
                'pin' => null,
                'title' => (string) $place->title,
                'url' => localized_route('guide.show', [$place]),
                'image' => $place->coverUrl('thumb'),
                'meta' => (string) $place->type->getLabel(),
            ])
            ->all();
    }

    /**
     * @return list<array{author: string, initials: string, rating: int, body: ?string, date: string, reply: ?string}>
     */
    public function reviews(Model $record, int $limit = 6): array
    {
        if (! method_exists($record, 'reviews')) {
            return [];
        }

        return $record->reviews()
            ->where('status', ReviewStatus::Published)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Review $review): array => [
                'author' => $review->user->name ?? __('site.owner.private'),
                'initials' => $review->user?->initials() ?? '•',
                'rating' => (int) $review->rating,
                'body' => $review->body,
                'date' => $this->dates->date($review->created_at ?? now()),
                'reply' => $review->owner_reply,
            ])
            ->all();
    }

    /**
     * Meta description from the item's text.
     */
    public function description(Model $record): ?string
    {
        return $this->cards->excerpt($record->getAttribute('description') ?? $record->getAttribute('excerpt'), 160);
    }
}
