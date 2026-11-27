<?php

namespace App\Support\Listings;

use App\Enums\ApplyMethod;
use App\Enums\CommercialSubtype;
use App\Enums\Currency;
use App\Enums\ListedBy;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SalaryPeriod;
use App\Enums\VacationType;
use App\Listings\Registry;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\AttributeSchema;
use App\Support\Geo\SeaDistance;
use App\Support\GeorgianPhone;
use Illuminate\Database\Eloquent\Model;

/**
 * The add-listing wizard's form: steps, defaults, validation per step and module, and the mapping
 * between the form array and the listing models. The wizard (Livewire) and the submit action both
 * go through here, so a listing is validated the same way whichever path saved it.
 *
 * Steps, fields and rules come from the listing registry. This class keeps the form array, the
 * stored-column mapping, and the same public constants the wizard already calls.
 *
 * The form is one array: common keys (title, description, location, photos, contact) plus the
 * keys of the chosen module.
 */
final class ListingForm
{
    /** @var list<string> */
    public const array STEPS = Registry::STEPS;

    /** @var list<string> */
    public const array TYPES = Registry::MODULES;

    public const int MAX_PHOTOS = Registry::MAX_PHOTOS;

    /** @var list<string> */
    public const array LANGUAGES = Registry::LANGUAGES;

    /** Kobuleti municipality: pins outside are rejected. */
    public const array BOUNDS = Registry::BOUNDS;

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
            'property' => [
                'rubric' => 'real_estate', 'deal' => '', 'property_type' => '', 'commercial_subtype' => '',
                'vacation_type' => '', 'area_m2' => '', 'land_area_m2' => '', 'rooms' => '', 'bedrooms' => '',
                'bathrooms' => '', 'floor' => '', 'total_floors' => '', 'condition' => '', 'building_status' => '',
                'cadastral_code' => '', 'listed_by' => ListedBy::Owner->value, 'features' => [],
                'purpose' => '', 'utilities' => [], 'guests' => '', 'distance_to_sea_m' => '',
                'check_in' => '14:00', 'check_out' => '12:00', 'stars' => '', 'rooms_count' => '', 'website' => '',
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
            $listing instanceof Property => self::propertyForm($listing),
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
        if (! in_array($type, self::TYPES, true)) {
            $rules = $step === 'type' ? ['type' => ['required']] : [];
        } else {
            $rules = self::registry()->forForm($type, $form)->rulesFor($step, $form);
        }

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
     * Human names for validation messages. Classified schema fields use their translated label.
     *
     * @param  array<string, mixed>|null  $form
     * @return array<string, string>
     */
    public static function validationAttributes(?array $form = null): array
    {
        $names = [];

        foreach (__('account.wizard.fields') as $key => $label) {
            if (is_string($label)) {
                $names['form.'.$key] = $label;
            }
        }

        foreach (['ka', 'en', 'ru'] as $locale) {
            $names["form.title.{$locale}"] = __('account.wizard.fields.title').' ('.strtoupper($locale).')';
            $names["form.description.{$locale}"] = __('account.wizard.fields.description').' ('.strtoupper($locale).')';
        }

        $category = Category::query()->find(self::int($form['category_id'] ?? null));

        if ($category !== null) {
            foreach ($category->schema()->fields() as $field) {
                $names['form.attribute_values.'.$field['key']] = AttributeSchema::label($field);
            }
        }

        return $names;
    }

    public static function requiresPhotos(string $type): bool
    {
        return self::registry()->forModule($type)?->media()->required ?? false;
    }

    public static function requiresPin(string $type): bool
    {
        return self::registry()->forModule($type)?->location()->pinRequired ?? false;
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
        $form = self::normalize($type, $form);
        $lat = self::float($form['lat'] ?? null);
        $lng = self::float($form['lng'] ?? null);
        $visible = self::registry()->forForm($type, $form)->visibleFieldNames($form);
        $keep = function (string $name, mixed $value) use ($visible): mixed {
            return in_array($name, $visible, true) ? $value : null;
        };

        $common = [
            'location_id' => self::int($form['location_id'] ?? null),
            'lat' => $lat,
            'lng' => $lng,
        ];

        return $common + match ($type) {
            'property' => self::propertyAttributes($form, $keep, $lat, $lng),
            'classified' => [
                'category_id' => self::int($form['category_id']),
                'condition' => self::nullable($keep('condition', $form['condition'] ?? null)),
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
    private static function propertyForm(Property $listing): array
    {
        $value = fn (string $field): string => self::string($listing->getAttribute($field));
        $clock = function (string $field) use ($listing): string {
            $raw = $listing->getAttribute($field);

            if ($raw === null || $raw === '') {
                return '';
            }

            return substr((string) $raw, 0, 5);
        };
        $vacation = $listing->isVacation();

        return [
            'rubric' => $vacation ? 'vacation' : 'real_estate',
            'deal' => $value('deal'),
            'property_type' => $value('type'),
            'commercial_subtype' => $value('commercial_subtype'),
            'vacation_type' => $value('vacation_type'),
            'area_m2' => self::number($listing->getAttribute('area_m2')),
            'land_area_m2' => self::number($listing->getAttribute('land_area_m2')),
            'rooms' => $value('rooms'),
            'bedrooms' => $value('bedrooms'),
            'bathrooms' => $value('bathrooms'),
            'floor' => $value('floor'),
            'total_floors' => $value('total_floors'),
            'condition' => $value('condition'),
            'building_status' => $value('building_status'),
            'cadastral_code' => $value('cadastral_code'),
            'listed_by' => $value('listed_by'),
            'features' => $listing->features()->pluck('amenities.id')->map(fn (mixed $id): string => (string) $id)->all(),
            'purpose' => $value('purpose'),
            'utilities' => array_values($listing->utilities ?? []),
            'guests' => $value('guests'),
            'distance_to_sea_m' => $vacation ? $value('distance_to_sea_m') : '',
            'check_in' => $clock('check_in'),
            'check_out' => $clock('check_out'),
            'stars' => $value('stars'),
            'rooms_count' => $value('rooms_count'),
            'website' => $value('website'),
            'price' => self::number($listing->getAttribute('price')),
            'currency' => $value('currency'),
        ];
    }

    /**
     * Bring a draft onto the current rubric: a hotel-as-a-business becomes commercial, and a
     * daily-rent hotel becomes a vacation stay. Stored columns are not renamed here.
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function normalize(string $type, array $form): array
    {
        if ($type !== 'property') {
            return $form;
        }

        if (($form['property_type'] ?? '') === PropertyType::HotelBusiness->value) {
            $form['property_type'] = PropertyType::Commercial->value;
            $form['commercial_subtype'] = $form['commercial_subtype'] ?: CommercialSubtype::HotelBusiness->value;
            $form['rubric'] = 'real_estate';
        }

        $legacyHotel = ($form['property_type'] ?? '') === PropertyType::Hotel->value
            && ($form['deal'] ?? '') === PropertyDeal::DailyRent->value;

        if (($form['rubric'] ?? '') === '' && (filled($form['vacation_type'] ?? null) || $legacyHotel)) {
            $form['rubric'] = 'vacation';
        }

        if (($form['rubric'] ?? '') === '') {
            $form['rubric'] = 'real_estate';
        }

        if ($form['rubric'] === 'vacation') {
            $form['deal'] = PropertyDeal::DailyRent->value;

            if (blank($form['vacation_type'] ?? null) && in_array($form['property_type'] ?? '', [
                PropertyType::Hotel->value,
                PropertyType::Apartment->value,
                PropertyType::House->value,
            ], true)) {
                $form['vacation_type'] = (string) $form['property_type'];
            }
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $form
     * @param  callable(string, mixed): mixed  $keep
     * @return array<string, mixed>
     */
    private static function propertyAttributes(array $form, callable $keep, ?float $lat, ?float $lng): array
    {
        $vacation = self::registry()->isVacation($form);
        $vacationType = $vacation ? VacationType::tryFrom((string) ($form['vacation_type'] ?? '')) : null;
        $type = $vacationType instanceof VacationType
            ? $vacationType->storedPropertyType()->value
            : (string) ($form['property_type'] ?: PropertyType::Apartment->value);

        if ($type === PropertyType::HotelBusiness->value) {
            $type = PropertyType::Commercial->value;
        }

        $utilities = array_values(array_filter(
            (array) $keep('utilities', $form['utilities'] ?? []),
            fn (mixed $item): bool => is_string($item) && $item !== '',
        ));
        $enteredDistance = self::int($keep('distance_to_sea_m', $form['distance_to_sea_m'] ?? null));

        return [
            'deal' => $vacation ? PropertyDeal::DailyRent->value : (string) $form['deal'],
            'type' => $type,
            'commercial_subtype' => self::nullable($keep('commercial_subtype', $form['commercial_subtype'] ?? null)),
            'vacation_type' => $vacationType?->value,
            'purpose' => self::nullable($keep('purpose', $form['purpose'] ?? null)),
            'utilities' => $utilities === [] ? null : $utilities,
            'area_m2' => self::float($keep('area_m2', $form['area_m2'] ?? null)),
            'land_area_m2' => self::float($keep('land_area_m2', $form['land_area_m2'] ?? null)),
            'rooms' => self::int($keep('rooms', $form['rooms'] ?? null)),
            'bedrooms' => self::int($keep('bedrooms', $form['bedrooms'] ?? null)),
            'bathrooms' => self::int($keep('bathrooms', $form['bathrooms'] ?? null)),
            'floor' => self::int($keep('floor', $form['floor'] ?? null)),
            'total_floors' => self::int($keep('total_floors', $form['total_floors'] ?? null)),
            'condition' => self::nullable($keep('condition', $form['condition'] ?? null)),
            'building_status' => self::nullable($keep('building_status', $form['building_status'] ?? null)),
            'cadastral_code' => self::nullable($keep('cadastral_code', $form['cadastral_code'] ?? null)),
            'listed_by' => in_array('listed_by', self::registry()->forForm('property', $form)->visibleFieldNames($form), true)
                ? ((string) ($form['listed_by'] ?: ListedBy::Owner->value))
                : ListedBy::Owner->value,
            'guests' => self::int($keep('guests', $form['guests'] ?? null)),
            'distance_to_sea_m' => $vacation && $enteredDistance !== null ? $enteredDistance : SeaDistance::meters($lat, $lng),
            'check_in' => self::nullable($keep('check_in', $form['check_in'] ?? null)),
            'check_out' => self::nullable($keep('check_out', $form['check_out'] ?? null)),
            'stars' => self::int($keep('stars', $form['stars'] ?? null)),
            'rooms_count' => self::int($keep('rooms_count', $form['rooms_count'] ?? null)),
            'website' => self::nullable($keep('website', $form['website'] ?? null)),
            'price' => (float) $form['price'],
            'currency' => $form['currency'],
            'contact_name' => self::nullable($form['contact_name'] ?? null),
            'contact_phone' => self::phone($form['contact_phone'] ?? null),
        ];
    }

    private static function registry(): Registry
    {
        return app(Registry::class);
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
