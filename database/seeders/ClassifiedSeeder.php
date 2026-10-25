<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\CategoryModule;
use App\Enums\Currency;
use App\Enums\ItemCondition;
use App\Enums\ListingStatus;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Classified;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * 80 classifieds: cars and phones with full attribute values, household goods, harvest and services.
 */
class ClassifiedSeeder extends Seeder
{
    use SeedsDemoData;

    private const array SERVICE_CATEGORIES = ['repair-construction', 'transfers', 'cleaning', 'tours'];

    private const array LOCATIONS = ['kobuleti-centre', 'kobuleti-centre', 'north-kobuleti', 'north-kobuleti', 'south-kobuleti', 'chakvi', 'tsikhisdziri', 'ochkhamuri', 'bobokvati'];

    /** @var array<int, ListingStatus> */
    private const array STATUSES = [7 => ListingStatus::Pending, 33 => ListingStatus::Pending, 48 => ListingStatus::Pending, 55 => ListingStatus::Rejected, 62 => ListingStatus::Draft, 70 => ListingStatus::Expired];

    /** @var Collection<string, Category> */
    private Collection $categories;

    /** @var Collection<int, BusinessProfile> */
    private Collection $services;

    /** @var array<string, array{ka: string, en: string, ru: string}> */
    private array $descriptions;

    private int $position = 0;

    public function run(): void
    {
        $data = require __DIR__.'/data/classifieds.php';

        $this->descriptions = $data['descriptions'];
        $this->categories = Category::query()->with('parent.parent')->where('module', CategoryModule::Classifieds)->get()->keyBy('slug');
        $this->services = BusinessProfile::query()->where('type', BusinessType::Service)->get();

        foreach ($data['cars'] as [$make, $makeName, $model, $year, $mileage, $fuel, $transmission, $steering, $price]) {
            $this->seedClassified('cars', Kobuleti::same("{$makeName} {$model}, {$year}"), $price, 'used', [
                'make' => $make, 'model' => $model, 'year' => $year, 'mileage' => $mileage, 'fuel' => $fuel,
                'transmission' => $transmission, 'steering' => $steering, 'customs_cleared' => fake()->boolean(85),
            ], Currency::USD);
        }

        foreach ($data['phones'] as [$brand, $model, $storage, $price, $condition]) {
            $this->seedClassified('phones', Kobuleti::same("{$model}, {$storage} GB"), $price, $condition, ['brand' => $brand, 'storage_gb' => $storage]);
        }

        foreach ($data['items'] as $item) {
            [$category, $ka, $en, $ru, $price, $condition] = $item;

            $this->seedClassified($category, ['ka' => $ka, 'en' => $en, 'ru' => $ru], $price, $condition, $item[6] ?? []);
        }
    }

    /**
     * @param  array{ka: string, en: string, ru: string}  $title
     * @param  array<string, mixed>  $attributes
     */
    private function seedClassified(string $slug, array $title, int|float|null $price, ?string $condition, array $attributes = [], Currency $currency = Currency::GEL): void
    {
        $category = $this->categories->get($slug) ?? throw new \InvalidArgumentException("Unknown classifieds category [{$slug}].");
        $isService = in_array($slug, self::SERVICE_CATEGORIES, true);
        $business = $isService && fake()->boolean(60) ? $this->services->random() : null;

        $this->withDemoStatus(Classified::factory(), $this->position++, self::STATUSES)->create([
            'user_id' => $business->user_id ?? $this->individuals()->random()->id,
            'business_profile_id' => $business?->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => $this->descriptions[$this->descriptionKey($slug, $condition)],
            ...$this->placeAt(fake()->randomElement(self::LOCATIONS)),
            'price' => $price,
            'currency' => $currency,
            'negotiable' => $price === null || fake()->boolean(30),
            'condition' => $condition !== null ? ItemCondition::from($condition) : null,
            'attribute_values' => $attributes === [] ? [] : $category->schema()->validate($attributes),
            'contact_name' => Kobuleti::firstName(),
            'contact_phone' => $business->phone ?? Kobuleti::phone(),
        ]);
    }

    private function descriptionKey(string $slug, ?string $condition): string
    {
        return match (true) {
            $slug === 'cars' => 'car',
            in_array($slug, self::SERVICE_CATEGORIES, true) => 'service',
            in_array($slug, ['garden-harvest', 'animals'], true) => 'goods',
            default => $condition === 'new' ? 'new' : 'used',
        };
    }
}
