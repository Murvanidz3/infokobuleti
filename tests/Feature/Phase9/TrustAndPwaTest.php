<?php

use App\Enums\Role;
use App\Enums\SiteFeature;
use App\Filament\Pages\SiteFeatures;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Livewire\Site\ReviewForm;
use App\Models\BookingRequest;
use App\Models\Conversation;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use App\Services\Trust\ReviewEligibility;
use App\Support\Features;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->guest = User::factory()->create();
    $this->hotel = Hotel::factory()->published()->create();
});

it('lets only guests who booked or talked to the hotel write a review', function () {
    $eligibility = app(ReviewEligibility::class);

    expect($eligibility->reason($this->guest, $this->hotel))->toBe('no_contact')
        ->and($eligibility->reason($this->hotel->user, $this->hotel))->toBe('own');

    BookingRequest::factory()->create(['hotel_id' => $this->hotel->id, 'user_id' => $this->guest->id]);
    expect($eligibility->canReview($this->guest, $this->hotel))->toBeTrue();

    // A conversation counts once the hotel side answered.
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['subject_type' => 'hotel', 'subject_id' => $this->hotel->id]);
    $conversation->participants()->attach([$other->id, $this->hotel->user_id]);
    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);
    expect($eligibility->canReview($other, $this->hotel))->toBeFalse();

    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $this->hotel->user_id]);
    expect($eligibility->canReview($other, $this->hotel))->toBeTrue();

    Features::set(SiteFeature::TrustLayer, false);
    expect($eligibility->canReview(User::factory()->create(), $this->hotel))->toBeTrue();
});

it('sends a review to moderation from the hotel page', function () {
    BookingRequest::factory()->create(['hotel_id' => $this->hotel->id, 'user_id' => $this->guest->id]);

    $this->actingAs($this->guest)->get(localized_route('hotels.show', [$this->hotel]))->assertOk()->assertSee(__('site.reviews.form.title'));
    $this->get(localized_route('hotels.show', [$this->hotel]))->assertOk();

    Livewire::actingAs($this->guest)->test(ReviewForm::class, ['hotelId' => $this->hotel->id])
        ->set('rating', 9)
        ->set('body', 'სუფთა ოთახები და ზღვის ხედი.')
        ->call('submit')
        ->assertSet('sent', true);

    expect(Review::query()->sole())->rating->toBe(9)->status->value->toBe('pending');

    Livewire::actingAs($this->guest)->test(ReviewForm::class, ['hotelId' => $this->hotel->id])
        ->assertSee(__('site.reviews.form.blocked.already'));
});

it('marks a property "owner verified" from the admin', function () {
    [$admin] = staffMember(Role::Admin);
    $this->actingAs($admin);
    Filament::setServingStatus();
    $property = Property::factory()->published()->create();

    Livewire::test(ViewProperty::class, ['record' => $property->getRouteKey()])->callAction('ownerVerified');

    expect($property->fresh())->owner_verified_at->not->toBeNull()->owner_verified_by->toBe($admin->id);

    Filament::setServingStatus(false);
    $this->get(localized_route('real-estate.show', [$property]))->assertSee(__('site.owner.owner_verified'));
});

it('switches features from the admin', function () {
    [$admin] = staffMember(Role::SuperAdmin);
    $this->actingAs($admin);
    Filament::setServingStatus();

    expect(Features::active(SiteFeature::Pwa))->toBeTrue();
    Livewire::test(SiteFeatures::class)->assertSee(SiteFeature::Pwa->label())->call('toggle', 'pwa');
    expect(Features::active(SiteFeature::Pwa))->toBeFalse();

    Filament::setServingStatus(false);
    $this->get('/')->assertDontSee('manifest.webmanifest');
});

it('serves the installable app files and the offline page', function () {
    expect(json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true))
        ->toMatchArray(['display' => 'standalone', 'theme_color' => '#0a1a2f'])
        ->and(file_get_contents(public_path('sw.js')))->toContain("addEventListener('push'");

    $this->get('/')->assertSee('manifest.webmanifest')->assertSee('name="pwa" content="1"', false);
    $this->get('/offline')->assertOk()->assertSee(__('pwa.offline.title'));
});
