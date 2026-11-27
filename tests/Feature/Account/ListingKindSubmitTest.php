<?php

use App\Enums\CategoryModule;
use App\Enums\ItemCondition;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Livewire\ListingWizard;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    $this->user = User::factory()->create(['phone' => '+995599123456', 'phone_verified_at' => now()]);
    $parent = Location::factory()->create(['parent_id' => null]);
    $this->location = Location::factory()->create(['parent_id' => $parent->id, 'lat' => 41.8180, 'lng' => 41.7765]);
});

it('creates each property subtype with the same stored columns', function (string $propertyType, array $details, array $expected) {
    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('form.deal', PropertyDeal::Sale->value)
        ->set('form.property_type', $propertyType)
        ->call('next')
        ->set('form.title.ka', 'განცხადება ზღვის პირას')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.area_m2', $details['area_m2'])
        ->set('form.land_area_m2', $details['land_area_m2'])
        ->set('form.rooms', $details['rooms'])
        ->call('next')
        ->set('form.location_id', (string) $this->location->id)
        ->call('setPin', 41.8193, 41.7769)
        ->call('next')
        ->set('upload', UploadedFile::fake()->image('photo.jpg', 1200, 900))
        ->call('next')
        ->set('form.price', '1000')
        ->set('form.currency', 'USD')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    $property = Property::query()->sole();

    expect($property->status)->toBe(ListingStatus::Pending)
        ->and($property->deal)->toBe(PropertyDeal::Sale)
        ->and($property->type)->toBe(PropertyType::from($propertyType))
        ->and($property->area_m2)->toBe($expected['area_m2'])
        ->and($property->land_area_m2)->toBe($expected['land_area_m2'])
        ->and($property->rooms)->toBe($expected['rooms'])
        ->and((float) $property->price)->toBe(1000.0)
        ->and($property->currency->value)->toBe('USD')
        ->and($property->location_id)->toBe($this->location->id)
        ->and($property->getMedia('gallery'))->toHaveCount(1);
})->with([
    'house' => ['house', ['area_m2' => '120', 'land_area_m2' => '40', 'rooms' => ''], ['area_m2' => 120.0, 'land_area_m2' => 40.0, 'rooms' => null]],
    'land' => ['land', ['area_m2' => '', 'land_area_m2' => '800', 'rooms' => '4'], ['area_m2' => null, 'land_area_m2' => 800.0, 'rooms' => 4]],
    'commercial' => ['commercial', ['area_m2' => '90', 'land_area_m2' => '', 'rooms' => ''], ['area_m2' => 90.0, 'land_area_m2' => null, 'rooms' => null]],
    'hotel business' => ['hotel_business', ['area_m2' => '500', 'land_area_m2' => '', 'rooms' => '12'], ['area_m2' => 500.0, 'land_area_m2' => null, 'rooms' => 12]],
]);

it('creates a service classified with item condition still stored', function () {
    $services = Category::factory()->create([
        'module' => CategoryModule::Classifieds,
        'parent_id' => null,
        'slug' => 'services',
    ]);
    $cleaning = Category::factory()->create([
        'module' => CategoryModule::Classifieds,
        'parent_id' => $services->id,
        'slug' => 'cleaning',
    ]);

    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'classified')
        ->set('form.parent_category_id', (string) $services->id)
        ->set('form.category_id', (string) $cleaning->id)
        ->call('next')
        ->set('form.title.ka', 'ბინის დალაგება')
        ->set('form.description.ka', 'ვალაგებთ ბინებს და სასტუმროს ნომრებს სეზონის განმავლობაში.')
        ->set('form.condition', ItemCondition::Used->value)
        ->call('next')
        ->set('form.location_id', (string) $this->location->id)
        ->call('next')
        ->call('next')
        ->set('form.price', '')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    $classified = Classified::query()->sole();

    expect($classified->status)->toBe(ListingStatus::Pending)
        ->and($classified->category_id)->toBe($cleaning->id)
        ->and($classified->condition)->toBe(ItemCondition::Used)
        ->and($classified->price)->toBeNull()
        ->and($classified->currency->value)->toBe('GEL')
        ->and($classified->negotiable)->toBeFalse()
        ->and($classified->contact_phone)->toBe('+995599123456')
        ->and($classified->location_id)->toBe($this->location->id)
        ->and($classified->lat)->toBeNull()
        ->and($classified->getMedia('gallery'))->toHaveCount(0);
});
