<?php

use App\Enums\ListingStatus;
use App\Livewire\ListingWizard;
use App\Models\Property;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('saves a published listing edit without sending it back to moderation', function () {
    $user = User::factory()->create();
    [$property] = editablePropertyDraft($user);

    actingAs($user);

    Livewire::test(ListingWizard::class, ['editType' => 'property', 'editId' => $property->id])
        ->set('form.price', '42000')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(localized_route('account.listings', ['type' => 'property', 'group' => 'active']))
        ->assertSessionHas('status', __('account.wizard.saved'));

    expect($property->fresh())
        ->status->toBe(ListingStatus::Published)
        ->price->toEqual(42000);
});

it('opens an archived listing and sends it to moderation on submit', function () {
    $user = User::factory()->create();
    [$property] = editablePropertyDraft($user, ListingStatus::Archived);

    actingAs($user);

    Livewire::test(ListingWizard::class, ['editType' => 'property', 'editId' => $property->id])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(localized_route('account.listings', ['type' => 'property', 'group' => 'pending']));

    expect($property->fresh()->status)->toBe(ListingStatus::Pending);
});

it('shows a validation error when the photo list was tampered with', function () {
    $user = User::factory()->create();
    [$property] = editablePropertyDraft($user);

    actingAs($user);

    Livewire::test(ListingWizard::class, ['editType' => 'property', 'editId' => $property->id])
        ->set('form.photos', ['nope'])
        ->call('submit')
        ->assertHasErrors(['form.photos.0' => __('account.wizard.errors.photo')]);

    expect($property->fresh()->status)->toBe(ListingStatus::Published)
        ->and($property->fresh()->price)->toEqual(50000);
});

it('ignores a tampered photo entry when one is removed', function () {
    $user = User::factory()->create();
    [$property] = editablePropertyDraft($user);

    actingAs($user);

    Livewire::test(ListingWizard::class, ['editType' => 'property', 'editId' => $property->id])
        ->set('form.photos', ['nope'])
        ->call('removePhoto', 0)
        ->assertSet('form.photos', ['nope']);

    expect(Property::query()->find($property->id)->status)->toBe(ListingStatus::Published);
});
