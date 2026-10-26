<?php

namespace App\Livewire\Site;

use App\Models\Hotel;
use App\Models\Review;
use App\Models\User;
use App\Services\Trust\ReviewEligibility;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Write a review" on a hotel page, for guests who booked or exchanged messages (trust layer).
 * Reviews go to moderation before they appear.
 */
class ReviewForm extends Component
{
    #[Locked]
    public int $hotelId = 0;

    public int $rating = 0;

    public string $body = '';

    public bool $sent = false;

    public function submit(ReviewEligibility $eligibility): void
    {
        $user = auth()->user();
        $hotel = $this->subject();

        if (! $user instanceof User || $hotel === null || ! $eligibility->canReview($user, $hotel)) {
            $this->dispatch('toast', message: __('site.reviews.form.not_allowed'), type: 'error');

            return;
        }

        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,10'],
            'body' => ['nullable', 'string', 'min:10', 'max:2000'],
        ], [], ['rating' => __('site.reviews.form.rating'), 'body' => __('site.reviews.form.body')]);

        $review = new Review([
            'rating' => $data['rating'],
            'body' => filled($data['body']) ? trim($data['body']) : null,
            'locale' => app()->getLocale(),
        ]);
        $review->user()->associate($user);
        $review->reviewable()->associate($hotel);
        $review->save();

        $this->sent = true;
        $this->dispatch('toast', message: __('site.reviews.form.sent'), type: 'success');
    }

    public function render(ReviewEligibility $eligibility): View
    {
        $user = auth()->user();
        $hotel = $this->subject();

        return view('livewire.site.review-form', [
            'guest' => ! $user instanceof User,
            'reason' => $user instanceof User && $hotel !== null ? $eligibility->reason($user, $hotel) : null,
        ]);
    }

    private function subject(): ?Model
    {
        return Hotel::query()->published()->find($this->hotelId);
    }
}
