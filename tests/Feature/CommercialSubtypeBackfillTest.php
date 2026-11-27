<?php

use App\Enums\CommercialSubtype;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Livewire\ListingWizard;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * @return object{up: callable, down: callable}
 */
function commercialSubtypeBackfill(): object
{
    return include database_path('migrations/2026_11_27_230000_backfill_null_commercial_subtypes.php');
}

/**
 * @param  array<string, mixed>  $attributes
 */
function commercialProperty(User $user, array $attributes = []): Property
{
    return Property::factory()->for($user)->type(PropertyType::Commercial, PropertyDeal::Sale)->create([
        'commercial_subtype' => null,
        ...$attributes,
    ]);
}

it('assigns other to legacy commercial listings and leaves hotel businesses alone', function () {
    $user = User::factory()->create();
    $migration = commercialSubtypeBackfill();
    $legacy = commercialProperty($user);
    $second = commercialProperty($user);
    $office = commercialProperty($user, ['commercial_subtype' => CommercialSubtype::Office->value]);
    $hotelBusiness = commercialProperty($user, ['commercial_subtype' => CommercialSubtype::HotelBusiness->value]);
    $clearedHotel = commercialProperty($user);
    $apartment = Property::factory()->for($user)->type(PropertyType::Apartment, PropertyDeal::Sale)->create([
        'commercial_subtype' => null,
    ]);

    DB::table('property_taxonomy_snapshots')->insert([
        ['property_id' => $hotelBusiness->id, 'type' => 'hotel_business'],
        ['property_id' => $clearedHotel->id, 'type' => 'hotel_business'],
    ]);

    $migration->up();
    $migration->up();

    $this->assertDatabaseHas('properties', ['id' => $legacy->id, 'commercial_subtype' => 'other']);
    $this->assertDatabaseHas('properties', ['id' => $second->id, 'commercial_subtype' => 'other']);
    $this->assertDatabaseHas('property_taxonomy_snapshots', ['property_id' => $legacy->id, 'type' => 'commercial']);
    $this->assertDatabaseHas('property_taxonomy_snapshots', ['property_id' => $second->id, 'type' => 'commercial']);
    $this->assertDatabaseHas('properties', ['id' => $office->id, 'commercial_subtype' => 'office']);
    $this->assertDatabaseHas('properties', ['id' => $hotelBusiness->id, 'commercial_subtype' => 'hotel_business']);
    $this->assertDatabaseHas('properties', ['id' => $clearedHotel->id, 'commercial_subtype' => null]);
    $this->assertDatabaseHas('properties', ['id' => $apartment->id, 'commercial_subtype' => null]);
    $this->assertDatabaseHas('property_taxonomy_snapshots', ['property_id' => $hotelBusiness->id, 'type' => 'hotel_business']);
    $this->assertDatabaseHas('property_taxonomy_snapshots', ['property_id' => $clearedHotel->id, 'type' => 'hotel_business']);

    expect(DB::table('property_taxonomy_snapshots')->where('type', 'commercial')->count())->toBe(2)
        ->and($legacy->fresh()->commercial_subtype)->toBe(CommercialSubtype::Other);
});

it('restores only the backfilled rows that are still other', function () {
    $user = User::factory()->create();
    $migration = commercialSubtypeBackfill();
    $legacy = commercialProperty($user);
    $edited = commercialProperty($user);
    $hotelBusiness = commercialProperty($user, ['commercial_subtype' => CommercialSubtype::HotelBusiness->value]);

    DB::table('property_taxonomy_snapshots')->insert([
        'property_id' => $hotelBusiness->id,
        'type' => 'hotel_business',
    ]);

    $migration->up();

    $edited->forceFill(['commercial_subtype' => CommercialSubtype::Office->value])->save();
    $chosen = commercialProperty($user, ['commercial_subtype' => CommercialSubtype::Other->value]);

    $migration->down();

    $this->assertDatabaseHas('properties', ['id' => $legacy->id, 'commercial_subtype' => null]);
    $this->assertDatabaseHas('properties', ['id' => $edited->id, 'commercial_subtype' => 'office']);
    $this->assertDatabaseHas('properties', ['id' => $chosen->id, 'commercial_subtype' => 'other']);
    $this->assertDatabaseHas('properties', ['id' => $hotelBusiness->id, 'commercial_subtype' => 'hotel_business']);
    $this->assertDatabaseMissing('property_taxonomy_snapshots', ['property_id' => $legacy->id]);
    $this->assertDatabaseMissing('property_taxonomy_snapshots', ['property_id' => $edited->id]);
    $this->assertDatabaseHas('property_taxonomy_snapshots', ['property_id' => $hotelBusiness->id, 'type' => 'hotel_business']);
    expect(DB::table('property_taxonomy_snapshots')->where('type', 'commercial')->count())->toBe(0);

    $migration->up();

    $this->assertDatabaseHas('properties', ['id' => $legacy->id, 'commercial_subtype' => 'other']);
    $this->assertDatabaseHas('properties', ['id' => $edited->id, 'commercial_subtype' => 'office']);
    $this->assertDatabaseHas('properties', ['id' => $chosen->id, 'commercial_subtype' => 'other']);
    $this->assertDatabaseMissing('property_taxonomy_snapshots', ['property_id' => $chosen->id]);
});

it('lets the owner edit a legacy commercial listing after the backfill and shows the other label', function () {
    app()->setLocale('ka');
    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $user = User::factory()->create(['phone' => '+995599123456', 'phone_verified_at' => now()]);
    $parent = Location::factory()->create(['parent_id' => null]);
    $location = Location::factory()->create(['parent_id' => $parent->id, 'lat' => 41.8180, 'lng' => 41.7765]);
    $property = Property::factory()->published()->for($user)->type(PropertyType::Commercial, PropertyDeal::Sale)->create([
        'commercial_subtype' => null,
        'title' => ['ka' => 'ძველი კომერციული ფართი', 'en' => 'Old commercial space', 'ru' => 'Старое помещение'],
        'description' => ['ka' => 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.'],
        'location_id' => $location->id,
        'lat' => 41.82,
        'lng' => 41.78,
        'contact_phone' => '+995599123456',
        'area_m2' => 90,
    ]);
    $property->addMedia(UploadedFile::fake()->image('photo.jpg', 1200, 900))->toMediaCollection('gallery');

    commercialSubtypeBackfill()->up();

    $property->refresh();

    expect($property->commercial_subtype)->toBe(CommercialSubtype::Other);

    $this->get(localized_route('real-estate.show', [$property], 'ka'))
        ->assertOk()
        ->assertSeeInOrder([__('account.wizard.fields.commercial_subtype', [], 'ka'), 'სხვა']);
    $this->get(localized_route('real-estate.show', [$property], 'en'))
        ->assertOk()
        ->assertSeeInOrder([__('account.wizard.fields.commercial_subtype', [], 'en'), 'Other']);
    $this->get(localized_route('real-estate.show', [$property], 'ru'))
        ->assertOk()
        ->assertSeeInOrder([__('account.wizard.fields.commercial_subtype', [], 'ru'), 'Другое']);

    app()->setLocale('ka');

    $wizard = Livewire::actingAs($user)->test(ListingWizard::class, ['editType' => 'property', 'editId' => $property->id])
        ->assertSet('form.commercial_subtype', 'other')
        ->call('goTo', 1);

    expect(array_key_last($wizard->viewData('options')['commercialSubtypes']))->toBe('other')
        ->and($wizard->viewData('options')['commercialSubtypes']['other'])->toBe('სხვა');

    $wizard->assertSeeHtmlInOrder(['ოფისი', 'მაღაზია', 'საწყობი', 'სასტუმრო/ბიზნესი', 'სხვა'])
        ->set('form.title.ka', 'განახლებული კომერციული ფართი')
        ->call('goTo', 6)
        ->call('submit')
        ->assertHasNoErrors();

    $fresh = $property->fresh();

    expect($fresh->commercial_subtype)->toBe(CommercialSubtype::Other)
        ->and($fresh->status)->toBe(ListingStatus::Pending)
        ->and($fresh->getTranslation('title', 'ka'))->toBe('განახლებული კომერციული ფართი');
});
