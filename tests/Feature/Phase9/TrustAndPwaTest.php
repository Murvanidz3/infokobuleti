<?php

use App\Enums\Role;
use App\Enums\SiteFeature;
use App\Filament\Pages\SiteFeatures;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Place;
use App\Models\Property;
use App\Models\User;
use App\Services\Trust\ReviewEligibility;
use App\Support\Features;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->guest = User::factory()->create();
    $this->place = Place::factory()->published()->create();
});

it('lets only guests who talked to the place write a review', function () {
    $eligibility = app(ReviewEligibility::class);

    expect($eligibility->reason($this->guest, $this->place))->toBe('no_contact')
        ->and($eligibility->reason($this->place->user, $this->place))->toBe('own');

    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['subject_type' => 'place', 'subject_id' => $this->place->id]);
    $conversation->participants()->attach([$other->id, $this->place->user_id]);
    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);
    expect($eligibility->canReview($other, $this->place))->toBeFalse();

    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $this->place->user_id]);
    expect($eligibility->canReview($other, $this->place))->toBeTrue();

    Features::set(SiteFeature::TrustLayer, false);
    expect($eligibility->canReview(User::factory()->create(), $this->place))->toBeTrue();
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
