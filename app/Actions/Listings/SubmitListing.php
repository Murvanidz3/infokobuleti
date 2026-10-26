<?php

namespace App\Actions\Listings;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Exceptions\ListingLimitReached;
use App\Models\Hotel;
use App\Models\ListingDraft;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Account\OwnerListings;
use App\Services\Listings\ListingLimits;
use App\Support\Listings\ListingForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Turns a wizard draft into a listing (new) or applies it to the listing being edited, and sends
 * the result to the moderation queue. The draft and its leftover photos are removed afterwards.
 */
class SubmitListing
{
    public function __construct(
        private OwnerListings $listings,
        private ListingLimits $limits,
    ) {}

    /**
     * @throws ValidationException when the form is incomplete
     * @throws ListingLimitReached when the monthly free listings are used up
     */
    public function handle(User $user, ListingDraft $draft): Model&Listing
    {
        $type = (string) $draft->listing_type;
        $form = $draft->data;

        Validator::make(['form' => $form], ListingForm::allRules($type, $form), [], ListingForm::validationAttributes())->validate();

        $editing = $draft->listing_id !== null ? $this->listings->find($user, $type, $draft->listing_id) : null;

        if ($editing === null && ! $this->limits->canCreate($user)) {
            throw new ListingLimitReached((int) $this->limits->monthlyLimit($user));
        }

        return DB::transaction(function () use ($user, $draft, $type, $form, $editing): Model&Listing {
            /** @var Model&Listing $listing */
            $listing = $editing ?? new (OwnerListings::TYPES[$type]);
            $listing->forceFill(['user_id' => $user->getKey()]);
            $listing->fill(ListingForm::attributes($type, $form));

            foreach (['title', 'description'] as $field) {
                $listing->setTranslations($field, array_filter($form[$field] ?? [], fn (mixed $text): bool => filled($text)));
            }

            $listing->setTranslations('address', array_filter(['ka' => trim((string) ($form['address'] ?? ''))], fn (string $text): bool => $text !== ''));

            if ($listing instanceof Vacancy && $listing->business_profile_id === null) {
                $listing->business_profile_id = $user->businessProfiles()->oldest('id')->value('id');
            }

            $listing->save();

            $this->syncRelations($listing, $form);
            $this->syncPhotos($listing, $draft, $form['photos'] ?? []);

            if ($listing->listingStatus() !== ListingStatus::Pending) {
                $listing->transitionTo(ListingStatus::Pending, by: $user);
            }

            $draft->delete();

            return $listing;
        });
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function syncRelations(Model $listing, array $form): void
    {
        if ($listing instanceof Hotel) {
            $listing->amenities()->sync(array_map('intval', $form['amenities'] ?? []));

            if (! ($form['has_rooms'] ?? false) && filled($form['room']['name'] ?? null)) {
                $listing->roomTypes()->create([
                    'name' => ['ka' => trim((string) $form['room']['name'])],
                    'capacity_adults' => (int) $form['room']['capacity_adults'],
                    'base_price' => (float) $form['room']['base_price'],
                    'units' => (int) $form['room']['units'],
                ]);
            }
        }

        if ($listing instanceof Property) {
            $listing->features()->sync(array_map('intval', $form['features'] ?? []));
        }
    }

    /**
     * Gallery = the photos listed in the form, in that order (the first is the cover). Photos
     * uploaded in the wizard move from the draft; gallery photos removed in the wizard are deleted.
     *
     * @param  list<array{source: string, id: int}>  $photos
     */
    private function syncPhotos(Model&Listing $listing, ListingDraft $draft, array $photos): void
    {
        $keep = collect($photos)->where('source', 'listing')->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        $listing->getMedia('gallery')
            ->reject(fn (Media $media): bool => in_array((int) $media->getKey(), $keep, true))
            ->each(fn (Media $media) => $media->delete());

        $draftMedia = $draft->getMedia('photos')->keyBy(fn (Media $media): int => (int) $media->getKey());
        $order = [];

        foreach ($photos as $photo) {
            $id = (int) $photo['id'];

            if ($photo['source'] === 'listing' && in_array($id, $keep, true)) {
                $order[] = $id;
            } elseif ($photo['source'] === 'draft' && $draftMedia->has($id)) {
                $order[] = (int) $draftMedia->get($id)->move($listing, 'gallery')->getKey();
            }
        }

        if ($order !== []) {
            Media::setNewOrder($order);
        }
    }
}
