<?php

namespace App\Support\Listings;

use App\Enums\AmenityScope;
use App\Enums\ApplyMethod;
use App\Enums\BuildingStatus;
use App\Enums\CategoryModule;
use App\Enums\Currency;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\HotelType;
use App\Enums\ItemCondition;
use App\Enums\ListedBy;
use App\Enums\NearbyPlace;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SalaryPeriod;
use App\Enums\VacancyBenefit;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Hotel;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Geo\SeaDistance;
use App\Support\GeorgianPhone;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * The add-listing wizard's form: steps, defaults, validation per step and module, and the mapping
 * between the form array and the listing models. The wizard (Livewire) and the submit action both
 * go through here, so a listing is validated the same way whichever path saved it.
 *
 * The form is one array: common keys (title, description, location, photos, contact) plus the
 * keys of the chosen module.
 */
final class ListingForm
{
    /** @var list<string> */
    public const array STEPS = ['type', 'details', 'location', 'photos', 'price', 'preview'];

    /** @var list<string> */
    public const array TYPES = ['property', 'hotel', 'classified', 'vacancy'];

    public const int MAX_PHOTOS = 20;

    /** @var list<string> */
    public const array LANGUAGES = ['ka', 'en', 'ru', 'tr', 'de'];

    /** Kobuleti municipality: pins outside are rejected. */
    public const array BOUNDS = ['south' => 41.60, 'north' => 41.96, 'west' => 41.62, 'east' => 42.15];

    private const string PHONE_PATTERN = '/^[\d\s()+-]{9,24}$/';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(string $type, User $user): array
    {
        $phone = $user->phone !== null ? GeorgianPhone::format($user->phone) : '';

        $common = [
            'title' => ['ka' => '', 'en' => '', 'ru' => ''],
            'description' => ['ka' => '', 'en' => '', 'ru' => ''],
            'location_id' => '',
            'address' => '',
            'lat' => null,
            'lng' => null,
            'photos' => [],
            'contact_name' => (string) $user->name,
            'contact_phone' => $phone,
        ];

        return $common + match ($type) {
            'hotel' => [
                'hotel_type' => '', 'stars' => '', 'rooms_count' => '', 'check_in_from' => '14:00', 'check_out_until' => '12:00',
                'season_start_month' => '', 'season_end_month' => '', 'amenities' => [],
                'contact_email' => (string) $user->email, 'website' => '',
                'room' => ['name' => '', 'capacity_adults' => '2', 'base_price' => '', 'units' => '1'],
                'has_rooms' => false,
            ],
            'property' => [
                'deal' => '', 'property_type' => '', 'area_m2' => '', 'land_area_m2' => '', 'rooms' => '', 'bedrooms' => '',
                'bathrooms' => '', 'floor' => '', 'total_floors' => '', 'condition' => '', 'building_status' => '',
                'cadastral_code' => '', 'listed_by' => ListedBy::Owner->value, 'features' => [], 'nearby' => [],
                'price' => '', 'currency' => Currency::USD->value,
            ],
            'classified' => [
                'parent_category_id' => '', 'category_id' => '', 'condition' => '', 'attribute_values' => [],
                'price' => '', 'currency' => Currency::GEL->value, 'negotiable' => false,
            ],
            'vacancy' => [
                'category_id' => '', 'employment_type' => '', 'experience' => '', 'languages' => ['ka'], 'benefits' => [],
                'deadline' => '', 'salary_min' => '', 'salary_max' => '', 'salary_currency' => Currency::GEL->value,
                'salary_period' => SalaryPeriod::Month->value, 'apply_method' => ApplyMethod::Site->value,
                'apply_phone' => $phone, 'apply_email' => (string) $user->email,
            ],
            default => [],
        };
    }

    /**
     * The form of an existing listing (photos are filled by the wizard from the gallery).
     *
     * @return array<string, mixed>
     */
    public static function fromListing(Model $listing): array
    {
        $text = fn (string $field): array => array_merge(['ka' => '', 'en' => '', 'ru' => ''], array_map('strval', method_exists($listing, 'getTranslations') ? $listing->getTranslations($field) : []));
        $value = fn (string $field): string => self::string($listing->getAttribute($field));

        $form = [
            'title' => $text('title'),
            'description' => $text('description'),
            'location_id' => $value('location_id'),
            'address' => method_exists($listing, 'getTranslation') ? (string) $listing->getTranslation('address', 'ka', false) : '',
            'lat' => $listing->getAttribute('lat') !== null ? (float) $listing->getAttribute('lat') : null,
            'lng' => $listing->getAttribute('lng') !== null ? (float) $listing->getAttribute('lng') : null,
            'photos' => [],
            'contact_name' => $value('contact_name'),
            'contact_phone' => $listing->getAttribute('contact_phone') !== null ? GeorgianPhone::format((string) $listing->getAttribute('contact_phone')) : '',
        ];

        return $form + match (true) {
            $listing instanceof Hotel => [
                'hotel_type' => $value('type'), 'stars' => $value('stars'), 'rooms_count' => $value('rooms_count'),
                'check_in_from' => substr($value('check_in_from'), 0, 5), 'check_out_until' => substr($value('check_out_until'), 0, 5),
                'season_start_month' => $value('season_start_month'), 'season_end_month' => $value('season_end_month'),
                'amenities' => $listing->amenities()->pluck('amenities.id')->map(fn (mixed $id): string => (string) $id)->all(),
                'contact_email' => $value('contact_email'), 'website' => $value('website'),
                'room' => ['name' => '', 'capacity_adults' => '2', 'base_price' => '', 'units' => '1'],
                'has_rooms' => $listing->roomTypes()->exists(),
            ],
            $listing instanceof Property => [
                'deal' => $value('deal'), 'property_type' => $value('type'), 'area_m2' => self::number($listing->getAttribute('area_m2')),
                'land_area_m2' => self::number($listing->getAttribute('land_area_m2')), 'rooms' => $value('rooms'), 'bedrooms' => $value('bedrooms'),
                'bathrooms' => $value('bathrooms'), 'floor' => $value('floor'), 'total_floors' => $value('total_floors'),
                'condition' => $value('condition'), 'building_status' => $value('building_status'), 'cadastral_code' => $value('cadastral_code'),
                'listed_by' => $value('listed_by'),
                'features' => $listing->features()->pluck('amenities.id')->map(fn (mixed $id): string => (string) $id)->all(),
                'nearby' => array_values(array_map('strval', $listing->nearby ?? [])),
                'price' => self::number($listing->getAttribute('price')), 'currency' => $value('currency'),
            ],
            $listing instanceof Classified => [
                'parent_category_id' => self::string(Category::query()->whereKey($listing->category_id)->value('parent_id')),
                'category_id' => $value('category_id'), 'condition' => $value('condition'),
                'attribute_values' => array_map(fn (mixed $item): mixed => is_array($item) ? $item : self::string($item), $listing->attribute_values ?? []),
                'price' => self::number($listing->getAttribute('price')), 'currency' => $value('currency'), 'negotiable' => (bool) $listing->negotiable,
            ],
            $listing instanceof Vacancy => [
                'category_id' => $value('category_id'), 'employment_type' => $value('employment_type'), 'experience' => $value('experience'),
                'languages' => array_values($listing->languages ?? []), 'benefits' => array_values($listing->benefits ?? []),
                'deadline' => $listing->deadline?->toDateString() ?? '', 'salary_min' => $value('salary_min'), 'salary_max' => $value('salary_max'),
                'salary_currency' => $value('salary_currency'), 'salary_period' => $value('salary_period'), 'apply_method' => $value('apply_method'),
                'apply_phone' => $listing->apply_phone !== null ? GeorgianPhone::format($listing->apply_phone) : '', 'apply_email' => $value('apply_email'),
            ],
            default => [],
        };
    }

    /**
     * Validation rules of one step, keyed "form.*".
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function rules(string $type, string $step, array $form): array
    {
        $rules = match ($step) {
            'type' => self::typeRules($type),
            'details' => self::detailRules($type, $form),
            'location' => self::locationRules($type),
            'photos' => [
                'photos' => [self::requiresPhotos($type) ? 'required' : 'nullable', 'array', 'max:'.self::MAX_PHOTOS],
                'photos.*' => ['array'],
                'photos.*.source' => ['required', 'string', Rule::in(['draft', 'listing'])],
                'photos.*.id' => ['required', 'integer', 'min:1'],
            ],
            'price' => self::priceRules($type, $form),
            default => [],
        };

        $prefixed = [];

        foreach ($rules as $key => $rule) {
            $prefixed['form.'.$key] = $rule;
        }

        return $prefixed;
    }

    /**
     * Every step's rules (used on submit).
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function allRules(string $type, array $form): array
    {
        $rules = [];

        foreach (self::STEPS as $step) {
            $rules += self::rules($type, $step, $form);
        }

        return $rules;
    }

    /**
     * The step on which a form key is edited (to send the user back to the first error).
     *
     * @param  array<string, mixed>  $form
     */
    public static function stepOf(string $type, string $key, array $form): string
    {
        $key = preg_replace('/^form\./', '', $key) ?? $key;

        foreach (self::STEPS as $step) {
            foreach (array_keys(self::rules($type, $step, $form)) as $ruleKey) {
                $ruleKey = substr($ruleKey, 5);

                if ($ruleKey === $key || str_starts_with($key, rtrim($ruleKey, '.*').'.') || $ruleKey === explode('.', $key)[0]) {
                    return $step;
                }
            }
        }

        return 'details';
    }

    /**
     * Human names for validation messages.
     *
     * @return array<string, string>
     */
    public static function validationAttributes(): array
    {
        $names = [];

        foreach (__('account.wizard.fields') as $key => $label) {
            $names['form.'.$key] = (string) $label;
        }

        foreach (['ka', 'en', 'ru'] as $locale) {
            $names["form.title.{$locale}"] = __('account.wizard.fields.title').' ('.strtoupper($locale).')';
            $names["form.description.{$locale}"] = __('account.wizard.fields.description').' ('.strtoupper($locale).')';
        }

        foreach (['name', 'capacity_adults', 'base_price', 'units'] as $field) {
            $names["form.room.{$field}"] = __('account.wizard.room.'.$field);
        }

        return $names;
    }

    /**
     * Messages for rules whose default text would name an internal key.
     *
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        $photo = __('account.wizard.errors.photo');

        return [
            'form.photos.array' => $photo,
            'form.photos.*.array' => $photo,
            'form.photos.*.source.required' => $photo,
            'form.photos.*.source.in' => $photo,
            'form.photos.*.id.required' => $photo,
            'form.photos.*.id.integer' => $photo,
            'form.photos.*.id.min' => $photo,
        ];
    }

    public static function requiresPhotos(string $type): bool
    {
        return in_array($type, ['hotel', 'property'], true);
    }

    public static function requiresPin(string $type): bool
    {
        return in_array($type, ['hotel', 'property'], true);
    }

    /**
     * Column values for the listing model (translations, relations and photos are handled by the
     * submit action).
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function attributes(string $type, array $form): array
    {
        $lat = self::float($form['lat'] ?? null);
        $lng = self::float($form['lng'] ?? null);

        $common = [
            'location_id' => self::int($form['location_id'] ?? null),
            'lat' => $lat,
            'lng' => $lng,
        ];

        return $common + match ($type) {
            'hotel' => [
                'type' => $form['hotel_type'],
                'stars' => self::int($form['stars'] ?? null),
                'rooms_count' => self::int($form['rooms_count'] ?? null),
                'check_in_from' => self::nullable($form['check_in_from'] ?? null),
                'check_out_until' => self::nullable($form['check_out_until'] ?? null),
                'season_start_month' => self::int($form['season_start_month'] ?? null),
                'season_end_month' => self::int($form['season_end_month'] ?? null),
                'distance_to_sea_m' => SeaDistance::meters($lat, $lng),
                'contact_phone' => self::phone($form['contact_phone'] ?? null),
                'contact_email' => self::nullable($form['contact_email'] ?? null),
                'website' => self::nullable($form['website'] ?? null),
            ],
            'property' => [
                ...self::propertyAttributes($form),
                'distance_to_sea_m' => SeaDistance::meters($lat, $lng),
                'price' => (float) $form['price'],
                'currency' => $form['currency'],
                'contact_name' => self::nullable($form['contact_name'] ?? null),
                'contact_phone' => self::phone($form['contact_phone'] ?? null),
            ],
            'classified' => [
                'category_id' => self::int($form['category_id']),
                'condition' => self::nullable($form['condition'] ?? null),
                'attribute_values' => self::attributeValues($form),
                'price' => self::float($form['price'] ?? null),
                'currency' => $form['currency'] ?: Currency::GEL->value,
                'negotiable' => (bool) ($form['negotiable'] ?? false),
                'contact_name' => self::nullable($form['contact_name'] ?? null),
                'contact_phone' => self::phone($form['contact_phone'] ?? null),
            ],
            'vacancy' => [
                'category_id' => self::int($form['category_id']),
                'employment_type' => $form['employment_type'],
                'experience' => self::nullable($form['experience'] ?? null),
                'languages' => array_values(array_unique($form['languages'] ?? [])),
                'benefits' => array_values(array_unique($form['benefits'] ?? [])),
                'deadline' => self::nullable($form['deadline'] ?? null),
                'salary_min' => self::int($form['salary_min'] ?? null),
                'salary_max' => self::int($form['salary_max'] ?? null),
                'salary_currency' => $form['salary_currency'] ?: Currency::GEL->value,
                'salary_period' => $form['salary_period'] ?: SalaryPeriod::Month->value,
                'apply_method' => $form['apply_method'],
                'apply_phone' => self::phone($form['apply_phone'] ?? null),
                'apply_email' => self::nullable($form['apply_email'] ?? null),
            ],
            default => [],
        };
    }

    /**
     * Category-schema attribute values of a classified, typed and limited to known keys.
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function attributeValues(array $form): array
    {
        $category = Category::query()->find(self::int($form['category_id'] ?? null));

        if ($category === null) {
            return [];
        }

        $values = [];

        foreach ($category->schema()->fields() as $field) {
            $value = $form['attribute_values'][$field['key']] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $values[$field['key']] = match ($field['type']) {
                'number' => is_numeric($value) ? $value + 0 : null,
                'boolean' => (bool) $value,
                'multiselect' => array_values((array) $value),
                default => (string) $value,
            };
        }

        return array_filter($values, fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function typeRules(string $type): array
    {
        return match ($type) {
            'hotel' => ['hotel_type' => ['required', Rule::enum(HotelType::class)]],
            'property' => [
                'deal' => ['required', Rule::enum(PropertyDeal::class)],
                'property_type' => ['required', Rule::enum(PropertyType::class)],
            ],
            'classified' => [
                'category_id' => ['required', 'integer', self::leafCategory(CategoryModule::Classifieds)],
            ],
            'vacancy' => [
                'category_id' => ['required', 'integer', self::leafCategory(CategoryModule::Vacancies)],
                'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            ],
            default => ['type' => ['required']],
        };
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private static function detailRules(string $type, array $form): array
    {
        $rules = [
            'title.ka' => ['required', 'string', 'min:5', 'max:120'],
            'title.en' => ['nullable', 'string', 'max:120'],
            'title.ru' => ['nullable', 'string', 'max:120'],
            'description.ka' => ['required', 'string', 'min:20', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'description.ru' => ['nullable', 'string', 'max:5000'],
        ];

        return $rules + match ($type) {
            'hotel' => [
                'stars' => ['nullable', 'integer', 'between:1,5'],
                'rooms_count' => ['nullable', 'integer', 'between:1,1000'],
                'check_in_from' => ['nullable', 'date_format:H:i'],
                'check_out_until' => ['nullable', 'date_format:H:i'],
                'season_start_month' => ['nullable', 'integer', 'between:1,12', 'required_with:form.season_end_month'],
                'season_end_month' => ['nullable', 'integer', 'between:1,12', 'required_with:form.season_start_month'],
                'amenities' => ['array'],
                'amenities.*' => [Rule::exists('amenities', 'id')->where('scope', AmenityScope::Hotel->value)],
            ],
            'property' => self::propertyDetailRules($form),
            'classified' => [
                'condition' => ['nullable', Rule::enum(ItemCondition::class)],
                ...self::schemaRules($form),
            ],
            'vacancy' => [
                'experience' => ['nullable', Rule::enum(Experience::class)],
                'languages' => ['array'],
                'languages.*' => [Rule::in(self::LANGUAGES)],
                'benefits' => ['array'],
                'benefits.*' => [Rule::enum(VacancyBenefit::class)],
                'deadline' => ['nullable', 'date', 'after_or_equal:today', 'before:'.now()->addYear()->toDateString()],
            ],
            default => [],
        };
    }

    /**
     * Columns that depend on the property type. Irrelevant ones are stored as null.
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private static function propertyAttributes(array $form): array
    {
        $type = PropertyType::from((string) $form['property_type']);
        $keep = fn (string $field, mixed $value): mixed => $type->allows($field) ? $value : null;

        return [
            'deal' => $form['deal'],
            'type' => $type->value,
            'area_m2' => $keep('area_m2', self::float($form['area_m2'] ?? null)),
            'land_area_m2' => $keep('land_area_m2', self::float($form['land_area_m2'] ?? null)),
            'rooms' => $keep('rooms', self::int($form['rooms'] ?? null)),
            'bedrooms' => $keep('bedrooms', self::int($form['bedrooms'] ?? null)),
            'bathrooms' => $keep('bathrooms', self::int($form['bathrooms'] ?? null)),
            'floor' => $keep('floor', self::int($form['floor'] ?? null)),
            'total_floors' => $keep('total_floors', self::int($form['total_floors'] ?? null)),
            'condition' => $keep('condition', self::nullable($form['condition'] ?? null)),
            'building_status' => $keep('building_status', self::nullable($form['building_status'] ?? null)),
            'cadastral_code' => self::nullable($form['cadastral_code'] ?? null),
            'listed_by' => $form['listed_by'] ?: ListedBy::Owner->value,
            'nearby' => self::nearby($form['nearby'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private static function propertyDetailRules(array $form): array
    {
        $type = PropertyType::tryFrom((string) ($form['property_type'] ?? ''));
        $numeric = function (string $field, array $rules) use ($type): array {
            if ($type !== null && ! $type->allows($field)) {
                return ['prohibited'];
            }

            return [$type?->requires($field) ? 'required' : 'nullable', ...$rules];
        };

        return [
            'area_m2' => $numeric('area_m2', ['numeric', 'between:1,100000']),
            'land_area_m2' => $numeric('land_area_m2', ['numeric', 'between:1,10000000']),
            'rooms' => $numeric('rooms', ['integer', 'between:1,50']),
            'bedrooms' => $numeric('bedrooms', ['integer', 'between:0,50']),
            'bathrooms' => $numeric('bathrooms', ['integer', 'between:0,20']),
            'floor' => $numeric('floor', ['integer', 'between:-3,100']),
            'total_floors' => [
                ...$numeric('total_floors', ['integer', 'between:1,100']),
                ...($type === null || $type->allows('total_floors') ? [self::notBelow($form['floor'] ?? null)] : []),
            ],
            'condition' => $type !== null && ! $type->allows('condition') ? ['prohibited'] : ['nullable', Rule::enum(PropertyCondition::class)],
            'building_status' => $type !== null && ! $type->allows('building_status') ? ['prohibited'] : ['nullable', Rule::enum(BuildingStatus::class)],
            'cadastral_code' => ['nullable', 'string', 'max:32', 'regex:/^[\d.\/-]+$/'],
            'listed_by' => ['required', Rule::enum(ListedBy::class)],
            'features' => ['array'],
            'features.*' => [Rule::exists('amenities', 'id')->where('scope', AmenityScope::Property->value)],
            'nearby' => ['array'],
            'nearby.*' => [Rule::enum(NearbyPlace::class)],
        ];
    }

    /**
     * @return list<string>|null
     */
    private static function nearby(mixed $values): ?array
    {
        $places = [];

        foreach ((array) $values as $value) {
            $place = NearbyPlace::tryFrom((string) $value);

            if ($place !== null) {
                $places[] = $place->value;
            }
        }

        $places = array_values(array_unique($places));

        return $places === [] ? null : $places;
    }

    /**
     * @return array<string, mixed>
     */
    private static function locationRules(string $type): array
    {
        $pin = self::requiresPin($type) ? 'required' : 'nullable';

        return [
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'address' => ['nullable', 'string', 'max:190'],
            'lat' => [$pin, 'numeric', 'between:'.self::BOUNDS['south'].','.self::BOUNDS['north']],
            'lng' => [$pin, 'numeric', 'between:'.self::BOUNDS['west'].','.self::BOUNDS['east']],
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private static function priceRules(string $type, array $form): array
    {
        $phone = ['string', 'max:24', 'regex:'.self::PHONE_PATTERN];

        return match ($type) {
            'hotel' => ($form['has_rooms'] ?? false) ? self::hotelContactRules($phone) : [
                'room.name' => ['required', 'string', 'min:2', 'max:80'],
                'room.capacity_adults' => ['required', 'integer', 'between:1,20'],
                'room.base_price' => ['required', 'numeric', 'between:1,100000'],
                'room.units' => ['required', 'integer', 'between:1,500'],
                ...self::hotelContactRules($phone),
            ],
            'property' => [
                'price' => ['required', 'numeric', 'between:1,100000000000'],
                'currency' => ['required', Rule::enum(Currency::class)],
                'contact_name' => ['nullable', 'string', 'max:120'],
                'contact_phone' => ['required', ...$phone],
            ],
            'classified' => [
                'price' => ['nullable', 'numeric', 'between:0,1000000000'],
                'currency' => ['required', Rule::enum(Currency::class)],
                'negotiable' => ['boolean'],
                'contact_name' => ['nullable', 'string', 'max:120'],
                'contact_phone' => ['required', ...$phone],
            ],
            'vacancy' => [
                'salary_min' => ['nullable', 'integer', 'between:0,1000000'],
                'salary_max' => ['nullable', 'integer', 'between:0,1000000', self::notBelow($form['salary_min'] ?? null)],
                'salary_currency' => ['required', Rule::enum(Currency::class)],
                'salary_period' => ['required', Rule::enum(SalaryPeriod::class)],
                'apply_method' => ['required', Rule::enum(ApplyMethod::class)],
                'apply_phone' => [($form['apply_method'] ?? '') === ApplyMethod::Phone->value ? 'required' : 'nullable', ...$phone],
                'apply_email' => [($form['apply_method'] ?? '') === ApplyMethod::Email->value ? 'required' : 'nullable', 'email:rfc', 'max:190'],
            ],
            default => [],
        };
    }

    /**
     * @param  list<string>  $phone
     * @return array<string, mixed>
     */
    private static function hotelContactRules(array $phone): array
    {
        return [
            'contact_phone' => ['required', ...$phone],
            'contact_email' => ['nullable', 'email:rfc', 'max:190'],
            'website' => ['nullable', 'url:http,https', 'max:190'],
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private static function schemaRules(array $form): array
    {
        $category = Category::query()->find(self::int($form['category_id'] ?? null));

        if ($category === null) {
            return [];
        }

        return $category->schema()->rules('attribute_values');
    }

    /**
     * An active category of the module without subcategories (listings go into the most specific one).
     */
    private static function leafCategory(CategoryModule $module): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($module): void {
            $valid = is_numeric($value) && Category::query()
                ->whereKey((int) $value)
                ->where('module', $module->value)
                ->where('is_active', true)
                ->whereDoesntHave('children', fn ($query) => $query->where('is_active', true))
                ->exists();

            if (! $valid) {
                $fail(__('account.wizard.errors.category'));
            }
        };
    }

    /**
     * The value must not be lower than another (optional) field's value.
     */
    private static function notBelow(mixed $other): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($other): void {
            if (is_numeric($value) && is_numeric($other) && (float) $value < (float) $other) {
                $fail(__('account.wizard.errors.not_below'));
            }
        };
    }

    private static function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : (string) $value;
    }

    private static function int(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function phone(mixed $value): ?string
    {
        $value = self::nullable($value);

        return $value === null ? null : Contact::normalizePhone($value);
    }

    private static function string(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof \BackedEnum => (string) $value->value,
            default => (string) $value,
        };
    }

    private static function number(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $number = (float) $value;

        return floor($number) === $number ? (string) (int) $number : (string) $number;
    }
}
