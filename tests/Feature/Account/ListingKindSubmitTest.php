<?php

use App\Enums\CategoryModule;
use App\Enums\CommercialSubtype;
use App\Enums\ItemCondition;
use App\Enums\LandPurpose;
use App\Enums\LandUtility;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\VacationType;
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

it('creates an apartment with the number grid and without a plot size', function () {
    submitProperty([
        'deal' => PropertyDeal::Sale->value,
        'property_type' => PropertyType::Apartment->value,
        'area_m2' => '64',
        'rooms' => '3',
        'bedrooms' => '2',
        'floor' => '4',
        'total_floors' => '9',
        'land_area_m2' => '20',
    ]);

    $property = Property::query()->sole();

    expect($property->type)->toBe(PropertyType::Apartment)
        ->and($property->deal)->toBe(PropertyDeal::Sale)
        ->and($property->area_m2)->toBe(64.0)
        ->and($property->rooms)->toBe(3)
        ->and($property->bedrooms)->toBe(2)
        ->and($property->floor)->toBe(4)
        ->and($property->total_floors)->toBe(9)
        ->and($property->land_area_m2)->toBeNull()
        ->and($property->vacation_type)->toBeNull();
});

it('creates a house with yard area and without a floor of the building', function () {
    submitProperty([
        'deal' => PropertyDeal::Rent->value,
        'property_type' => PropertyType::House->value,
        'area_m2' => '120',
        'land_area_m2' => '40',
        'rooms' => '5',
        'floor' => '2',
        'total_floors' => '2',
    ]);

    $property = Property::query()->sole();

    expect($property->type)->toBe(PropertyType::House)
        ->and($property->deal)->toBe(PropertyDeal::Rent)
        ->and($property->area_m2)->toBe(120.0)
        ->and($property->land_area_m2)->toBe(40.0)
        ->and($property->rooms)->toBe(5)
        ->and($property->floor)->toBeNull()
        ->and($property->total_floors)->toBe(2);
});

it('creates land with purpose and utilities and stores no rooms', function () {
    submitProperty([
        'deal' => PropertyDeal::Sale->value,
        'property_type' => PropertyType::Land->value,
        'land_area_m2' => '800',
        'purpose' => LandPurpose::Agricultural->value,
        'utilities' => [LandUtility::Water->value, LandUtility::RoadAccess->value],
        'rooms' => '4',
        'area_m2' => '10',
        'condition' => 'newly_renovated',
    ]);

    $property = Property::query()->sole();

    expect($property->type)->toBe(PropertyType::Land)
        ->and($property->land_area_m2)->toBe(800.0)
        ->and($property->purpose)->toBe(LandPurpose::Agricultural)
        ->and($property->utilities)->toBe(['water', 'road_access'])
        ->and($property->rooms)->toBeNull()
        ->and($property->area_m2)->toBeNull()
        ->and($property->condition)->toBeNull()
        ->and($property->listed_by->value)->toBe('owner');
});

it('creates each commercial subtype once', function (string $subtype) {
    submitProperty([
        'deal' => PropertyDeal::Sale->value,
        'property_type' => PropertyType::Commercial->value,
        'commercial_subtype' => $subtype,
        'area_m2' => '90',
        'floor' => '1',
        'total_floors' => '3',
        'rooms' => '12',
        'bedrooms' => '4',
    ]);

    $property = Property::query()->sole();

    expect($property->type)->toBe(PropertyType::Commercial)
        ->and($property->commercial_subtype)->toBe(CommercialSubtype::from($subtype))
        ->and($property->area_m2)->toBe(90.0)
        ->and($property->floor)->toBe(1)
        ->and($property->rooms)->toBeNull()
        ->and($property->bedrooms)->toBeNull();
})->with(['office', 'shop', 'warehouse', 'hotel_business']);

it('creates each vacation type as a daily-rent stay', function (string $vacationType, string $storedType) {
    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseVacation')
        ->set('form.vacation_type', $vacationType)
        ->set('form.stars', '4')
        ->set('form.rooms_count', '8')
        ->call('next')
        ->set('form.title.ka', 'განცხადება ზღვის პირას')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.guests', '6')
        ->set('form.bedrooms', '2')
        ->set('form.distance_to_sea_m', '120')
        ->set('form.rooms', '9')
        ->call('next')
        ->set('form.location_id', (string) $this->location->id)
        ->call('setPin', 41.8193, 41.7769)
        ->call('next')
        ->set('upload', UploadedFile::fake()->image('photo.jpg', 1200, 900))
        ->call('next')
        ->set('form.price', '150')
        ->set('form.website', 'https://stay.example')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    $property = Property::query()->sole();
    $keepsRooms = in_array($vacationType, ['hotel', 'guesthouse', 'hostel'], true);

    expect($property->deal)->toBe(PropertyDeal::DailyRent)
        ->and($property->type)->toBe(PropertyType::from($storedType))
        ->and($property->vacation_type)->toBe(VacationType::from($vacationType))
        ->and($property->guests)->toBe(6)
        ->and($property->bedrooms)->toBe(2)
        ->and($property->distance_to_sea_m)->toBe(120)
        ->and($property->rooms)->toBeNull()
        ->and($property->stars)->toBe($vacationType === 'hotel' ? 4 : null)
        ->and($property->rooms_count)->toBe($keepsRooms ? 8 : null)
        ->and($property->website)->toBe('https://stay.example')
        ->and((float) $property->price)->toBe(150.0);
})->with([
    'hotel' => ['hotel', 'hotel'],
    'guesthouse' => ['guesthouse', 'hotel'],
    'apartment' => ['apartment', 'apartment'],
    'house' => ['house', 'house'],
    'hostel' => ['hostel', 'hotel'],
]);

function submitProperty(array $fields): void
{
    $wizard = Livewire::actingAs(test()->user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('form.deal', $fields['deal'])
        ->set('form.property_type', $fields['property_type']);

    foreach (['commercial_subtype', 'purpose', 'utilities'] as $key) {
        if (array_key_exists($key, $fields)) {
            $wizard->set('form.'.$key, $fields[$key]);
        }
    }

    $wizard->call('next')
        ->set('form.title.ka', 'განცხადება ზღვის პირას')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.');

    foreach (['area_m2', 'land_area_m2', 'rooms', 'bedrooms', 'floor', 'total_floors', 'condition'] as $key) {
        if (array_key_exists($key, $fields)) {
            $wizard->set('form.'.$key, $fields[$key]);
        }
    }

    $wizard->call('next')
        ->set('form.location_id', (string) test()->location->id)
        ->call('setPin', 41.8193, 41.7769)
        ->call('next')
        ->set('upload', UploadedFile::fake()->image('photo.jpg', 1200, 900))
        ->call('next')
        ->set('form.price', '1000')
        ->set('form.currency', 'USD')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();
}

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
        ->and($classified->condition)->toBeNull()
        ->and($classified->price)->toBeNull()
        ->and($classified->currency->value)->toBe('GEL')
        ->and($classified->negotiable)->toBeFalse()
        ->and($classified->contact_phone)->toBe('+995599123456')
        ->and($classified->location_id)->toBe($this->location->id)
        ->and($classified->lat)->toBeNull()
        ->and($classified->getMedia('gallery'))->toHaveCount(0);
});
