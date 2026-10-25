<?php

use App\Enums\BuildingStatus;
use App\Enums\CategoryModule;
use App\Enums\EmploymentType;
use App\Enums\ItemCondition;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Livewire\Listings\ClassifiedIndex;
use App\Livewire\Listings\PropertyIndex;
use App\Livewire\Listings\VacancyIndex;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Property;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function titles(mixed $component): array
{
    return collect($component->viewData('items'))->pluck('title')->sort()->values()->all();
}

function namedProperty(string $title, array $attributes = []): Property
{
    return Property::factory()->create(['title' => ['ka' => $title, 'en' => $title], ...$attributes]);
}

it('renders every module list page', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/real-estate', '/en/real-estate', '/classifieds', '/ru/classifieds', '/jobs', '/en/jobs']);

it('filters real estate by deal, type, area, rooms, floor and building age', function () {
    namedProperty('Flat 2', ['deal' => PropertyDeal::Sale, 'type' => PropertyType::Apartment, 'area_m2' => 64, 'rooms' => 2, 'floor' => 7, 'building_status' => BuildingStatus::New]);
    namedProperty('Flat 5', ['deal' => PropertyDeal::Sale, 'type' => PropertyType::Apartment, 'area_m2' => 140, 'rooms' => 5, 'floor' => 2, 'building_status' => BuildingStatus::Old]);
    namedProperty('House', ['deal' => PropertyDeal::Rent, 'type' => PropertyType::House, 'area_m2' => 180, 'rooms' => 4, 'floor' => null, 'building_status' => null]);

    expect(titles(Livewire::test(PropertyIndex::class)->set('deal', 'sale')))->toBe(['Flat 2', 'Flat 5'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('type', ['house'])))->toBe(['House'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('area_min', '100')))->toBe(['Flat 5', 'House'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('rooms', ['2'])))->toBe(['Flat 2'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('rooms', ['4'])))->toBe(['Flat 5', 'House'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('floor_min', '3')))->toBe(['Flat 2'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('building', 'new')))->toBe(['Flat 2'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('building', 'old')))->toBe(['Flat 5']);
});

it('compares real estate prices in GEL or USD', function () {
    namedProperty('Cheap', ['price' => 30000, 'currency' => 'USD']);
    namedProperty('Dear', ['price' => 400000, 'currency' => 'GEL']);
    $rate = app(App\Contracts\ExchangeRates::class)->rate(App\Enums\Currency::USD);

    expect(Property::query()->where('title->en', 'Cheap')->value('price_gel'))->toEqual(round(30000 * $rate, 2))
        ->and(titles(Livewire::test(PropertyIndex::class)->set('currency', 'USD')->set('price_max', '50000')))->toBe(['Cheap'])
        ->and(titles(Livewire::test(PropertyIndex::class)->set('price_min', '200000')))->toBe(['Dear']);
});

it('shows only listings with photos when asked', function () {
    $with = namedProperty('With photo');
    namedProperty('Without photo');
    DB::table('media')->insert([
        'model_type' => 'property', 'model_id' => $with->id, 'uuid' => (string) Str::uuid(), 'collection_name' => 'gallery',
        'name' => 'photo', 'file_name' => 'photo.jpg', 'mime_type' => 'image/jpeg', 'disk' => 'public', 'conversions_disk' => 'public',
        'size' => 100, 'manipulations' => '[]', 'custom_properties' => '[]', 'generated_conversions' => '[]', 'responsive_images' => '[]',
        'order_column' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(titles(Livewire::test(PropertyIndex::class)->set('photos', true)))->toBe(['With photo']);
});

it('filters classifieds by category, sub-categories and schema attributes', function () {
    $transport = Category::factory()->module(CategoryModule::Classifieds)->create(['slug' => 'transport', 'parent_id' => null]);
    $cars = Category::factory()->module(CategoryModule::Classifieds)->create([
        'slug' => 'cars',
        'parent_id' => $transport->id,
        'attribute_schema' => [
            ['key' => 'fuel', 'type' => 'select', 'label' => ['ka' => 'საწვავი', 'en' => 'Fuel'], 'options' => [['value' => 'petrol', 'label' => ['ka' => 'ბენზინი', 'en' => 'Petrol']], ['value' => 'diesel', 'label' => ['ka' => 'დიზელი', 'en' => 'Diesel']]], 'filterable' => true],
            ['key' => 'year', 'type' => 'number', 'label' => ['ka' => 'წელი', 'en' => 'Year'], 'filterable' => true],
            ['key' => 'customs_cleared', 'type' => 'boolean', 'label' => ['ka' => 'განბაჟებული', 'en' => 'Customs cleared'], 'filterable' => true],
        ],
    ]);
    $furniture = Category::factory()->module(CategoryModule::Classifieds)->create(['slug' => 'furniture', 'parent_id' => null]);

    $make = fn (string $title, Category $category, array $values = [], array $extra = []) => Classified::factory()->create(['title' => ['ka' => $title, 'en' => $title], 'category_id' => $category->id, 'attribute_values' => $values, ...$extra]);
    $make('Diesel 2015', $cars, ['fuel' => 'diesel', 'year' => 2015, 'customs_cleared' => true], ['condition' => ItemCondition::Used, 'price' => 9000]);
    $make('Petrol 2008', $cars, ['fuel' => 'petrol', 'year' => 2008, 'customs_cleared' => false], ['condition' => ItemCondition::Used, 'price' => 4000]);
    $make('Sofa', $furniture, [], ['condition' => ItemCondition::New, 'price' => 300]);

    expect(titles(Livewire::test(ClassifiedIndex::class)->set('category', 'transport')))->toBe(['Diesel 2015', 'Petrol 2008'])
        ->and(titles(Livewire::test(ClassifiedIndex::class)->set('category', 'cars')->set('attrs.fuel', 'petrol')))->toBe(['Petrol 2008'])
        ->and(titles(Livewire::test(ClassifiedIndex::class)->set('category', 'cars')->set('attrs.year_min', '2010')))->toBe(['Diesel 2015'])
        ->and(titles(Livewire::test(ClassifiedIndex::class)->set('category', 'cars')->set('attrs.customs_cleared', true)))->toBe(['Diesel 2015'])
        ->and(titles(Livewire::test(ClassifiedIndex::class)->set('condition', 'new')))->toBe(['Sofa'])
        ->and(titles(Livewire::test(ClassifiedIndex::class)->set('price_min', '1000')->set('price_max', '5000')))->toBe(['Petrol 2008']);

    // The panel offers the attributes of the chosen category, and changing category drops them.
    Livewire::test(ClassifiedIndex::class)
        ->set('category', 'cars')
        ->assertViewHas('fields', fn (array $fields) => array_column($fields, 'key') === ['fuel', 'year', 'customs_cleared'])
        ->set('attrs.fuel', 'diesel')
        ->set('category', 'furniture')
        ->assertSet('attrs', []);
});

it('filters jobs by category, employment type, salary and posting date', function () {
    $hospitality = Category::factory()->module(CategoryModule::Vacancies)->create(['slug' => 'hospitality', 'parent_id' => null]);
    $retail = Category::factory()->module(CategoryModule::Vacancies)->create(['slug' => 'retail', 'parent_id' => null]);
    $make = fn (string $title, array $extra) => Vacancy::factory()->create(['title' => ['ka' => $title, 'en' => $title], ...$extra]);
    $make('Waiter', ['category_id' => $hospitality->id, 'employment_type' => EmploymentType::Seasonal, 'salary_min' => 1200, 'salary_max' => 1500, 'published_at' => now()->subHours(5)]);
    $make('Cashier', ['category_id' => $retail->id, 'employment_type' => EmploymentType::FullTime, 'salary_min' => 900, 'salary_max' => null, 'published_at' => now()->subDays(10)]);

    expect(titles(Livewire::test(VacancyIndex::class)->set('category', 'hospitality')))->toBe(['Waiter'])
        ->and(titles(Livewire::test(VacancyIndex::class)->set('employment', ['full_time'])))->toBe(['Cashier'])
        ->and(titles(Livewire::test(VacancyIndex::class)->set('salary', '1000')))->toBe(['Waiter'])
        ->and(titles(Livewire::test(VacancyIndex::class)->set('posted', '3')))->toBe(['Waiter']);

    Livewire::test(VacancyIndex::class)->assertSet('display', 'list')->assertViewHas('views', ['list', 'map']);
});

it('never lists hidden or expired items in any module', function () {
    namedProperty('Live flat');
    namedProperty('Pending flat', ['status' => ListingStatus::Pending, 'published_at' => null]);
    namedProperty('Expired flat', ['expires_at' => now()->subDay()]);
    Classified::factory()->create(['title' => ['ka' => 'Live item', 'en' => 'Live item']]);
    Classified::factory()->draft()->create(['title' => ['ka' => 'Draft item', 'en' => 'Draft item']]);
    Vacancy::factory()->create(['title' => ['ka' => 'Live job', 'en' => 'Live job']]);
    Vacancy::factory()->create(['title' => ['ka' => 'Rejected job', 'en' => 'Rejected job'], 'status' => ListingStatus::Rejected]);

    expect(titles(Livewire::test(PropertyIndex::class)))->toBe(['Live flat'])
        ->and(titles(Livewire::test(ClassifiedIndex::class)))->toBe(['Live item'])
        ->and(titles(Livewire::test(VacancyIndex::class)))->toBe(['Live job']);
});
