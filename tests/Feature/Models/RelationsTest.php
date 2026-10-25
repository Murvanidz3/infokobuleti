<?php

use App\Enums\AmenityScope;
use App\Enums\BusinessType;
use App\Enums\PromotionType;
use App\Enums\ReviewStatus;
use App\Models\Amenity;
use App\Models\BookingRequest;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Place;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\Report;
use App\Models\Review;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Models\RoomTypePriceRule;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Illuminate\Support\Facades\DB;

it('links a hotel with its owner, business, room types, prices, availability and bookings', function () {
    $business = BusinessProfile::factory()->type(BusinessType::Hotel)->create();
    $hotel = Hotel::factory()->for($business->user)->for($business)->create();
    $room = RoomType::factory()->for($hotel)->create();
    RoomTypePriceRule::factory()->for($room, 'roomType')->create();
    RoomAvailability::factory()->for($room, 'roomType')->create();
    BookingRequest::factory()->for($hotel)->for($room, 'roomType')->create();

    $hotel = Hotel::query()->with(['user', 'businessProfile', 'roomTypes.priceRules', 'roomTypes.availability', 'bookingRequests.roomType'])->find($hotel->id);

    expect($hotel->user->is($business->user))->toBeTrue()
        ->and($hotel->businessProfile->is($business))->toBeTrue()
        ->and($hotel->roomTypes)->toHaveCount(1)
        ->and($hotel->roomTypes->first()->priceRules)->toHaveCount(1)
        ->and($hotel->roomTypes->first()->availability)->toHaveCount(1)
        ->and($hotel->bookingRequests->first()->roomType->is($room))->toBeTrue();
});

it('keeps hotel amenities, room amenities and property features apart', function () {
    $wifi = Amenity::factory()->scope(AmenityScope::Hotel)->create();
    $balcony = Amenity::factory()->scope(AmenityScope::Room)->create();
    $parking = Amenity::factory()->scope(AmenityScope::Property)->create();

    $hotel = Hotel::factory()->create();
    $hotel->amenities()->attach([$wifi->id, $balcony->id]);
    $property = Property::factory()->create();
    $property->features()->attach($parking);

    expect($hotel->amenities()->pluck('amenities.id')->all())->toBe([$wifi->id])
        ->and($property->features()->pluck('amenities.id')->all())->toBe([$parking->id])
        ->and(DB::table('amenityables')->where('amenityable_type', 'property')->count())->toBe(1);
});

it('connects vacancies with their employer, category and applications', function () {
    $employer = BusinessProfile::factory()->type(BusinessType::Employer)->create();
    $vacancy = Vacancy::factory()->for($employer->user)->for($employer)->create();
    $applicant = User::factory()->create();
    VacancyApplication::factory()->for($vacancy)->for($applicant)->create();

    $vacancy->load(['businessProfile', 'category', 'applications.user']);

    expect($vacancy->businessProfile->is($employer))->toBeTrue()
        ->and($vacancy->category)->toBeInstanceOf(Category::class)
        ->and($vacancy->applications->first()->user->is($applicant))->toBeTrue()
        ->and($employer->vacancies()->count())->toBe(1);
});

it('puts classifieds in a category and locations in a tree', function () {
    $parent = Location::factory()->create();
    $child = Location::factory()->create(['parent_id' => $parent->id]);
    $classified = Classified::factory()->create(['location_id' => $child->id]);

    $classified->load('category', 'location.parent');

    expect($classified->category->classifieds()->count())->toBe(1)
        ->and($classified->location->parent->is($parent))->toBeTrue()
        ->and($parent->children()->pluck('id')->all())->toBe([$child->id]);
});

it('ties events to their venue place', function () {
    $place = Place::factory()->create();
    Event::factory()->count(2)->create(['place_id' => $place->id]);

    expect($place->events()->count())->toBe(2);
});

it('stores morph aliases instead of class names', function () {
    $hotel = Hotel::factory()->create();
    $user = User::factory()->create();

    Favorite::factory()->for($user)->create(['favoritable_type' => $hotel->getMorphClass(), 'favoritable_id' => $hotel->id]);

    expect(DB::table('favorites')->value('favoritable_type'))->toBe('hotel')
        ->and($hotel->isFavoritedBy($user))->toBeTrue()
        ->and($hotel->isFavoritedBy(User::factory()->create()))->toBeFalse()
        ->and($user->favorites()->with('favoritable')->first()->favoritable->is($hotel))->toBeTrue();
});

it('caches the rating from published reviews only', function () {
    $hotel = Hotel::factory()->create();

    Review::factory()->create(['reviewable_type' => 'hotel', 'reviewable_id' => $hotel->id, 'rating' => 10]);
    Review::factory()->create(['reviewable_type' => 'hotel', 'reviewable_id' => $hotel->id, 'rating' => 7]);
    Review::factory()->pending()->create(['reviewable_type' => 'hotel', 'reviewable_id' => $hotel->id, 'rating' => 1]);

    expect($hotel->fresh())->rating_avg->toBe(8.5)->reviews_count->toBe(2);

    $hotel->reviews()->where('rating', 7)->first()->delete();

    expect($hotel->fresh())->rating_avg->toBe(10.0)->reviews_count->toBe(1);
});

it('reviews business profiles and places too', function (string $model) {
    $reviewable = $model::factory()->create();
    Review::factory()->create(['reviewable_type' => $reviewable->getMorphClass(), 'reviewable_id' => $reviewable->id, 'rating' => 9, 'status' => ReviewStatus::Published]);

    expect($reviewable->fresh()->rating_avg)->toBe(9.0);
})->with(['business profile' => [BusinessProfile::class], 'place' => [Place::class]]);

it('refreshes the promotion cache and orders Super VIP, VIP, bump, then date', function () {
    $plain = Property::factory()->create(['published_at' => now()->subHour()]);
    $bumped = Property::factory()->create(['published_at' => now()->subDays(5)]);
    $vip = Property::factory()->create(['published_at' => now()->subDays(9)]);
    $superVip = Property::factory()->create(['published_at' => now()->subDays(20)]);

    Promotion::factory()->type(PromotionType::Bump)->create(['promotable_id' => $bumped->id, 'starts_at' => now()->subMinutes(5)]);
    Promotion::factory()->type(PromotionType::Vip)->create(['promotable_id' => $vip->id]);
    Promotion::factory()->type(PromotionType::SuperVip)->create(['promotable_id' => $superVip->id]);

    expect($superVip->fresh()->promotion_level)->toBe(2)
        ->and($vip->fresh()->promoted_until)->not->toBeNull()
        ->and($bumped->fresh()->bumped_at)->not->toBeNull()
        ->and(Property::query()->orderByPromotion()->pluck('id')->all())->toBe([$superVip->id, $vip->id, $bumped->id, $plain->id]);
});

it('drops the promotion level when the promotion ends', function () {
    $property = Property::factory()->create();
    $promotion = Promotion::factory()->create(['promotable_id' => $property->id]);
    $payment = Payment::factory()->create();
    $promotion->update(['payment_id' => $payment->id]);

    expect($property->fresh()->promotion_level)->toBe(1)
        ->and($payment->promotions()->count())->toBe(1);

    $promotion->update(['ends_at' => now()->subMinute()]);

    expect($property->fresh()->promotion_level)->toBe(0);
});

it('keeps conversations with participants, messages and the latest message time', function () {
    $owner = User::factory()->create();
    $buyer = User::factory()->create();
    $property = Property::factory()->for($owner)->create();

    $conversation = Conversation::factory()->create(['subject_type' => 'property', 'subject_id' => $property->id]);
    $conversation->participants()->attach([$owner->id, $buyer->id]);
    Message::factory()->for($conversation)->for($buyer)->create();
    $last = Message::factory()->for($conversation)->for($owner)->create();

    $conversation->refresh()->load('subject', 'participants', 'messages');

    expect($conversation->subject->is($property))->toBeTrue()
        ->and($conversation->participants)->toHaveCount(2)
        ->and($conversation->messages)->toHaveCount(2)
        ->and($conversation->last_message_at->equalTo($last->created_at))->toBeTrue()
        ->and($buyer->conversations()->count())->toBe(1);
});

it('accepts reports on listings, reviews and messages', function () {
    $report = Report::factory()->create();
    $review = Review::factory()->create();
    Report::factory()->create(['reportable_type' => 'review', 'reportable_id' => $review->id]);

    expect($report->reportable)->toBeInstanceOf(Classified::class)
        ->and($review->reports()->count())->toBe(1);
});

it('gives each payment an idempotency key', function () {
    expect(Payment::factory()->create()->idempotency_key)->toMatch('/^[0-9a-f-]{36}$/');
});
