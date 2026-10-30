<?php

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\Amenity;
use App\Models\Article;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Location;
use App\Models\Place;
use App\Models\Project;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    config(['demo.password' => 'test-password']);
});

it('seeds the demo portal with the planned amount of content', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Property::count())->toBe(60)
        ->and(Project::count())->toBe(6)
        ->and(Classified::count())->toBe(80)
        ->and(Vacancy::count())->toBe(40)
        ->and(Place::count())->toBe(25)
        ->and(Article::count())->toBe(12)
        ->and(Event::count())->toBe(8);

    foreach (Role::cases() as $role) {
        expect(User::role($role->value)->exists())->toBeTrue("No demo user with role {$role->value}");
    }
});

it('seeds consistent listing data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Property::query()->whereNull('price_gel')->exists())->toBeFalse()
        ->and(Property::query()->where('type', 'country_house')->exists())->toBeFalse()
        ->and(Property::query()->where('type', 'hotel')->exists())->toBeTrue()
        ->and(Project::query()->whereNull('price_gel')->exists())->toBeFalse()
        ->and(Classified::query()->whereNotNull('price')->whereNull('price_gel')->exists())->toBeFalse()
        ->and(Property::query()->whereNull('location_id')->exists())->toBeFalse()
        ->and(Property::query()->where('promotion_level', 2)->count())->toBe(3)
        ->and(Review::query()->count())->toBeGreaterThan(20);

    foreach ([Property::class, Classified::class, Vacancy::class] as $model) {
        expect($model::query()->where('status', ListingStatus::Pending)->exists())->toBeTrue("{$model} has nothing to moderate");
    }

    $car = Classified::query()->whereHas('category', fn ($query) => $query->where('slug', 'cars'))->firstOrFail();

    expect($car->attribute_values)->toHaveKeys(['make', 'model', 'year', 'mileage', 'fuel', 'transmission']);
});

it('uses the configured password for staff accounts', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@infokobuleti.com')->firstOrFail();

    expect(Hash::check('test-password', $admin->password))->toBeTrue()
        ->and($admin->hasRole(Role::Admin->value))->toBeTrue();
});

it('can re-run the reference data without duplicates', function () {
    $this->seed(ReferenceDataSeeder::class);
    $counts = [SpatieRole::count(), Location::count(), Category::count(), Amenity::count()];

    $this->seed(ReferenceDataSeeder::class);

    expect([SpatieRole::count(), Location::count(), Category::count(), Amenity::count()])->toBe($counts)
        ->and(Location::query()->where('slug', 'kobuleti')->value('lat'))->not->toBeNull();
});
