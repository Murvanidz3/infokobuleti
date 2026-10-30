<?php

namespace App\Actions\Listings;

use App\Contracts\Listing;
use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Exceptions\ListingLimitReached;
use App\Jobs\AnnounceToModerators;
use App\Models\ListingDraft;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Account\OwnerListings;
use App\Services\Listings\ListingLimits;
use App\Services\Listings\ListingPhotos;
use App\Support\Listings\ListingForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Turns a wizard draft into a listing (new) or applies it to the listing being edited.
 *
 * A listing that is already published stays published: the wizard writes onto that same row,
 * and there is no second copy that could stay on the site while a revision waited. Sending it
 * back to pending is what takes it down, including for a price change. Every other status
 * (new, rejected, paused, expired, archived) still enters the moderation queue.
 */
class SubmitListing
{
    public function __construct(
        private OwnerListings $listings,
        private ListingLimits $limits,
        private ListingPhotos $photos,
    ) {}

    /**
     * @throws ValidationException when the form is incomplete or the status cannot be submitted
     * @throws ListingLimitReached when the monthly free listings are used up
     */
    public function handle(User $user, ListingDraft $draft): Model&Listing
    {
        $type = (string) $draft->listing_type;
        $form = $draft->data;

        Validator::make(
            ['form' => $form],
            ListingForm::allRules($type, $form),
            ListingForm::validationMessages(),
            ListingForm::validationAttributes(),
        )->validate();

        $editing = $draft->listing_id !== null
            ? $this->listings->find($user, $type, (int) $draft->listing_id, withArchived: true)
            : null;

        return DB::transaction(function () use ($user, $draft, $type, $form, $editing): Model&Listing {
            if ($editing === null) {
                // The limit is a count of rows. Hold the owner row so two submits cannot both
                // observe a free slot and both insert.
                User::query()->whereKey($user->getKey())->lockForUpdate()->first();

                if (! $this->limits->canCreate($user)) {
                    throw new ListingLimitReached((int) $this->limits->limitFor($user), $this->limits->packageLimit($user) !== null);
                }
            }

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

            $photos = $form['photos'] ?? [];

            if (! is_array($photos)) {
                throw ValidationException::withMessages([
                    'form.photos' => __('account.wizard.errors.photo'),
                ]);
            }

            $this->photos->sync($listing, $draft, $photos);

            $this->moveToQueueUnlessPublished($listing, $user);

            $draft->delete();

            if ($listing->listingStatus() === ListingStatus::Pending) {
                AnnounceToModerators::dispatch($listing->getMorphClass(), (int) $listing->getKey());
            }

            return $listing;
        });
    }

    /**
     * @throws ValidationException
     */
    private function moveToQueueUnlessPublished(Model&Listing $listing, User $user): void
    {
        if ($listing->listingStatus() === ListingStatus::Published) {
            return;
        }

        if ($listing->listingStatus() === ListingStatus::Pending) {
            return;
        }

        try {
            $listing->transitionTo(ListingStatus::Pending, by: $user);
        } catch (InvalidStatusTransition) {
            throw ValidationException::withMessages([
                'status' => __('account.wizard.errors.status'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function syncRelations(Model $listing, array $form): void
    {
        if ($listing instanceof Property) {
            $listing->features()->sync(array_map('intval', $form['features'] ?? []));
        }
    }
}
