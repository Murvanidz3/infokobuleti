<?php

namespace App\Listings;

use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Listings\Definitions\ApartmentDefinition;
use App\Listings\Definitions\ClassifiedGoodsDefinition;
use App\Listings\Definitions\ClassifiedServiceDefinition;
use App\Listings\Definitions\CommercialDefinition;
use App\Listings\Definitions\HouseDefinition;
use App\Listings\Definitions\LandDefinition;
use App\Listings\Definitions\VacancyDefinition;
use App\Listings\Definitions\VacationDefinition;
use App\Models\Category;
use InvalidArgumentException;

/**
 * Per-type listing definitions. Real estate is sale or monthly rent. Vacation is always daily
 * rent and is its own definition, even though both are stored on properties.
 */
final class Registry
{
    /** @var list<string> */
    public const array STEPS = ['type', 'details', 'location', 'photos', 'price', 'preview'];

    /** @var list<string> */
    public const array MODULES = ['property', 'classified', 'vacancy'];

    public const int MAX_PHOTOS = 20;

    /** @var list<string> */
    public const array LANGUAGES = ['ka', 'en', 'ru', 'tr', 'de'];

    /**
     * Kobuleti municipality: pins outside are rejected.
     *
     * @var array{south: float, north: float, west: float, east: float}
     */
    public const array BOUNDS = ['south' => 41.60, 'north' => 41.96, 'west' => 41.62, 'east' => 42.15];

    public const string PHONE_PATTERN = '/^[\d\s()+-]{9,24}$/';

    /** @var array<string, ListingDefinition> */
    private array $definitions;

    /** @var array<int, bool> */
    private array $serviceCategories = [];

    public function __construct()
    {
        $this->definitions = [];

        foreach ([
            new ApartmentDefinition,
            new HouseDefinition,
            new LandDefinition,
            new CommercialDefinition,
            new VacationDefinition,
            new ClassifiedGoodsDefinition,
            new ClassifiedServiceDefinition,
            new VacancyDefinition,
        ] as $definition) {
            $this->definitions[$definition->key()] = $definition;
        }
    }

    /**
     * @return array<string, ListingDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions);
    }

    public function get(string $key): ListingDefinition
    {
        return $this->definitions[$key] ?? throw new InvalidArgumentException("Unknown listing definition [{$key}].");
    }

    /**
     * A definition whose module-level rules (pin, photos) match every kind of that module.
     */
    public function forModule(string $module): ?ListingDefinition
    {
        $key = match ($module) {
            'property' => 'property.apartment',
            'classified' => 'classified.goods',
            'vacancy' => 'vacancy',
            default => null,
        };

        return $key === null ? null : $this->get($key);
    }

    /**
     * The definition for a wizard form. An unknown real-estate type uses the apartment rules.
     * A vacation form (rubric, vacation type, or a legacy daily-rent hotel) uses the vacation rules.
     *
     * @param  array<string, mixed>  $form
     */
    public function forForm(string $module, array $form): ListingDefinition
    {
        $key = match ($module) {
            'property' => $this->propertyKey($form),
            'classified' => $this->categoryIsService($form) ? 'classified.service' : 'classified.goods',
            'vacancy' => 'vacancy',
            default => throw new InvalidArgumentException("Unknown listing module [{$module}]."),
        };

        return $this->get($key);
    }

    /**
     * Whether this form is a daily-rent stay rather than a sale or monthly rent.
     *
     * @param  array<string, mixed>  $form
     */
    public function isVacation(array $form): bool
    {
        return $this->propertyKey($form) === 'property.vacation';
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function propertyKey(array $form): string
    {
        if (($form['rubric'] ?? '') === 'vacation' || filled($form['vacation_type'] ?? null)) {
            return 'property.vacation';
        }

        if (($form['property_type'] ?? '') === PropertyType::Hotel->value && ($form['deal'] ?? '') === PropertyDeal::DailyRent->value) {
            return 'property.vacation';
        }

        if (($form['property_type'] ?? '') === PropertyType::HotelBusiness->value) {
            return 'property.commercial';
        }

        $key = 'property.'.(string) ($form['property_type'] ?? '');

        return array_key_exists($key, $this->definitions) ? $key : 'property.apartment';
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function categoryIsService(array $form): bool
    {
        $id = $form['category_id'] ?? null;

        if (! is_numeric($id)) {
            return false;
        }

        $categoryId = (int) $id;

        if (array_key_exists($categoryId, $this->serviceCategories)) {
            return $this->serviceCategories[$categoryId];
        }

        $isService = $this->categoryChainIsService($categoryId);
        $this->serviceCategories[$categoryId] = $isService;

        return $isService;
    }

    private function categoryChainIsService(int $categoryId): bool
    {
        for ($depth = 0; $depth < 8 && $categoryId > 0; $depth++) {
            $category = Category::query()->find($categoryId);

            if (! $category instanceof Category) {
                return false;
            }

            if ($category->slug === ClassifiedServiceDefinition::CATEGORY_SLUG) {
                return true;
            }

            $categoryId = (int) $category->parent_id;
        }

        return false;
    }
}
