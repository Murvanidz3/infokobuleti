<?php

use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Article;
use App\Models\Classified;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

it('starts new listings as drafts', function () {
    expect((new Property)->status)->toBe(ListingStatus::Draft);
});

it('allows only the documented transitions', function (ListingStatus $from, ListingStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with(function () {
    $allowed = [
        'draft' => ['pending', 'published'],
        'pending' => ['published', 'rejected', 'draft'],
        'rejected' => ['pending'],
        'published' => ['paused', 'expired', 'archived', 'pending'],
        'paused' => ['published', 'pending', 'archived'],
        'expired' => ['pending', 'published', 'archived'],
        'archived' => ['draft', 'pending'],
    ];

    foreach (ListingStatus::cases() as $from) {
        foreach (ListingStatus::cases() as $to) {
            yield "{$from->value} -> {$to->value}" => [$from, $to, in_array($to->value, $allowed[$from->value], true)];
        }
    }
});

it('throws on a forbidden transition and leaves the listing untouched', function () {
    $property = Property::factory()->draft()->create();

    expect(fn () => $property->transitionTo(ListingStatus::Expired))->toThrow(InvalidStatusTransition::class);
    expect($property->fresh()->status)->toBe(ListingStatus::Draft);
});

it('sets publish and expiry dates per module when a moderator approves', function (string $model, int $days) {
    Carbon::setTestNow('2026-10-25 12:00:00');
    $moderator = User::factory()->create();

    /** @var Property|Classified|Vacancy|Hotel $listing */
    $listing = $model::factory()->pending()->create();
    $listing->transitionTo(ListingStatus::Published, by: $moderator);

    expect($listing->fresh())
        ->status->toBe(ListingStatus::Published)
        ->published_at->toDateTimeString()->toBe('2026-10-25 12:00:00')
        ->expires_at->toDateTimeString()->toBe(now()->addDays($days)->toDateTimeString())
        ->moderated_by->toBe($moderator->id);
})->with([
    'property' => [Property::class, 60],
    'classified' => [Classified::class, 30],
    'vacancy' => [Vacancy::class, 30],
    'hotel' => [Hotel::class, 365],
]);

it('never sets an expiry on modules that do not expire', function (string $model) {
    $listing = $model::factory()->draft()->create();
    $listing->transitionTo(ListingStatus::Published);

    expect($listing->fresh()->getAttributes())->not->toHaveKey('expires_at')
        ->and($listing->fresh()->published_at)->not->toBeNull();
})->with(['place' => [Place::class], 'article' => [Article::class]]);

it('stores the rejection reason and clears it on approval', function () {
    $listing = Classified::factory()->pending()->create();

    $listing->transitionTo(ListingStatus::Rejected, 'phone number in the title');
    expect($listing->fresh()->rejection_reason)->toBe('phone number in the title');

    $listing->transitionTo(ListingStatus::Pending);
    $listing->transitionTo(ListingStatus::Published);
    expect($listing->fresh()->rejection_reason)->toBeNull();
});

it('keeps the original expiry when a paused listing is resumed', function () {
    $listing = Property::factory()->published()->create();
    $expiresAt = $listing->expires_at;

    $this->travel(3)->days();
    $listing->transitionTo(ListingStatus::Paused);
    $listing->transitionTo(ListingStatus::Published);

    expect($listing->fresh()->expires_at->toDateTimeString())->toBe($expiresAt->toDateTimeString());
});

it('does not resume a paused listing past its expiry date', function () {
    $listing = Property::factory()->published()->create();
    $listing->transitionTo(ListingStatus::Paused);

    $this->travel(31)->days();

    expect(fn () => $listing->transitionTo(ListingStatus::Published))->toThrow(InvalidStatusTransition::class);
});

it('keeps the first publish date when a listing is renewed', function () {
    $listing = Vacancy::factory()->published()->create();
    $firstPublishedAt = $listing->published_at;

    $this->travel(40)->days();
    $listing->transitionTo(ListingStatus::Expired);
    $listing->transitionTo(ListingStatus::Pending);
    $listing->transitionTo(ListingStatus::Published);

    expect($listing->fresh())
        ->published_at->toDateTimeString()->toBe($firstPublishedAt->toDateTimeString())
        ->expires_at->isFuture()->toBeTrue();
});

it('writes every transition to the activity log', function () {
    $moderator = User::factory()->create();
    $listing = Classified::factory()->pending()->create();

    $listing->transitionTo(ListingStatus::Rejected, 'duplicate', $moderator);

    $activity = Activity::query()->where('log_name', 'moderation')->latest('id')->first();

    expect($activity)
        ->event->toBe('status_changed')
        ->subject_type->toBe('classified')
        ->subject_id->toBe($listing->id)
        ->causer_id->toBe($moderator->id)
        ->and($activity->properties->all())->toBe(['from' => 'pending', 'to' => 'rejected', 'reason' => 'duplicate']);
});

it('hides everything but published items from the published scope', function () {
    Classified::factory()->published()->count(2)->create();
    Classified::factory()->pending()->create();
    Classified::factory()->draft()->create();

    expect(Classified::query()->published()->count())->toBe(2);
});
