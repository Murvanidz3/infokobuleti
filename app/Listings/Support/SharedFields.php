<?php

namespace App\Listings\Support;

use App\Enums\ApplyMethod;
use App\Enums\BuildingStatus;
use App\Enums\CategoryModule;
use App\Enums\CommercialSubtype;
use App\Enums\Currency;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\ItemCondition;
use App\Enums\LandPurpose;
use App\Enums\LandUtility;
use App\Enums\ListedBy;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SalaryPeriod;
use App\Enums\VacancyBenefit;
use App\Enums\VacationType;
use App\Listings\Field;
use App\Listings\FieldOptions;
use App\Listings\FieldType;
use App\Listings\LocationRules;
use App\Listings\MediaRules;
use App\Listings\PriceModel;
use App\Listings\Registry;
use App\Listings\ShowWhen;
use Illuminate\Validation\Rule;

/**
 * Fields every listing kind reuses: title, description, place, photos, price and contact.
 * Module-only fields live here too so a definition composes them instead of copying rules.
 */
final class SharedFields
{
    /**
     * @return list<Field>
     */
    public static function titleAndDescription(): array
    {
        return [
            new Field(
                name: 'title',
                type: FieldType::TranslatableText,
                required: true,
                rules: fn (array $form): array => [
                    'title.ka' => ['required', 'string', 'min:5', 'max:120'],
                    'title.en' => ['nullable', 'string', 'max:120'],
                    'title.ru' => ['nullable', 'string', 'max:120'],
                ],
            ),
            new Field(
                name: 'description',
                type: FieldType::TranslatableTextarea,
                required: true,
                rules: fn (array $form): array => [
                    'description.ka' => ['required', 'string', 'min:20', 'max:5000'],
                    'description.en' => ['nullable', 'string', 'max:5000'],
                    'description.ru' => ['nullable', 'string', 'max:5000'],
                ],
            ),
        ];
    }

    /**
     * Sale or monthly rent, then one of the four real-estate types. Daily rent is a vacation listing.
     *
     * @return list<Field>
     */
    public static function realEstateType(): array
    {
        return [
            new Field(
                name: 'deal',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'deal' => ['required', Rule::in([PropertyDeal::Sale->value, PropertyDeal::Rent->value])],
                ],
                options: FieldOptions::Deals,
            ),
            new Field(
                name: 'property_type',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'property_type' => ['required', Rule::in([
                        PropertyType::Apartment->value,
                        PropertyType::House->value,
                        PropertyType::Land->value,
                        PropertyType::Commercial->value,
                    ])],
                ],
                options: FieldOptions::PropertyTypes,
            ),
        ];
    }

    public static function commercialSubtype(): Field
    {
        return new Field(
            name: 'commercial_subtype',
            type: FieldType::Select,
            required: true,
            rules: fn (array $form): array => [
                'commercial_subtype' => ['required', Rule::enum(CommercialSubtype::class)],
            ],
            options: FieldOptions::CommercialSubtypes,
        );
    }

    public static function vacationType(): Field
    {
        return new Field(
            name: 'vacation_type',
            type: FieldType::Select,
            required: true,
            rules: fn (array $form): array => [
                'vacation_type' => ['required', Rule::enum(VacationType::class)],
            ],
            options: FieldOptions::VacationTypes,
        );
    }

    public static function area(bool $required): Field
    {
        return new Field(
            name: 'area_m2',
            type: FieldType::Number,
            required: $required,
            rules: fn (array $form): array => [
                'area_m2' => [$required ? 'required' : 'nullable', 'numeric', 'between:1,100000'],
            ],
        );
    }

    public static function landArea(bool $required): Field
    {
        return new Field(
            name: 'land_area_m2',
            type: FieldType::Number,
            required: $required,
            rules: fn (array $form): array => [
                'land_area_m2' => [$required ? 'required' : 'nullable', 'numeric', 'between:1,10000000'],
            ],
        );
    }

    public static function rooms(): Field
    {
        return self::wholeNumber('rooms', 1, 50);
    }

    public static function bedrooms(): Field
    {
        return self::wholeNumber('bedrooms', 0, 50);
    }

    public static function bathrooms(): Field
    {
        return self::wholeNumber('bathrooms', 0, 20);
    }

    public static function floor(): Field
    {
        return self::wholeNumber('floor', -3, 100);
    }

    public static function totalFloors(bool $notBelowFloor = true): Field
    {
        return new Field(
            name: 'total_floors',
            type: FieldType::Integer,
            required: false,
            rules: fn (array $form): array => [
                'total_floors' => ['nullable', 'integer', 'between:1,100', ...($notBelowFloor ? [Constraints::notBelow($form['floor'] ?? null)] : [])],
            ],
        );
    }

    public static function condition(): Field
    {
        return new Field(
            name: 'condition',
            type: FieldType::Select,
            required: false,
            rules: fn (array $form): array => [
                'condition' => ['nullable', Rule::enum(PropertyCondition::class)],
            ],
            options: FieldOptions::PropertyConditions,
        );
    }

    public static function buildingStatus(): Field
    {
        return new Field(
            name: 'building_status',
            type: FieldType::Select,
            required: false,
            rules: fn (array $form): array => [
                'building_status' => ['nullable', Rule::enum(BuildingStatus::class)],
            ],
            options: FieldOptions::BuildingStatuses,
        );
    }

    public static function cadastralCode(): Field
    {
        return new Field(
            name: 'cadastral_code',
            type: FieldType::Text,
            required: false,
            rules: fn (array $form): array => [
                'cadastral_code' => ['nullable', 'string', 'max:32', 'regex:/^[\d.\/-]+$/'],
            ],
        );
    }

    public static function listedBy(): Field
    {
        return new Field(
            name: 'listed_by',
            type: FieldType::Select,
            required: true,
            rules: fn (array $form): array => [
                'listed_by' => ['required', Rule::enum(ListedBy::class)],
            ],
            options: FieldOptions::ListedBy,
        );
    }

    /**
     * @param  list<string>  $amenityScopes
     */
    public static function features(array $amenityScopes): Field
    {
        return new Field(
            name: 'features',
            type: FieldType::Multiselect,
            required: false,
            rules: fn (array $form): array => [
                'features' => ['array'],
                'features.*' => [Rule::exists('amenities', 'id')->whereIn('scope', $amenityScopes)],
            ],
            options: FieldOptions::PropertyFeatures,
        );
    }

    public static function landPurpose(): Field
    {
        return new Field(
            name: 'purpose',
            type: FieldType::Select,
            required: true,
            rules: fn (array $form): array => [
                'purpose' => ['required', Rule::enum(LandPurpose::class)],
            ],
            options: FieldOptions::LandPurposes,
        );
    }

    public static function landUtilities(): Field
    {
        return new Field(
            name: 'utilities',
            type: FieldType::Multiselect,
            required: false,
            rules: fn (array $form): array => [
                'utilities' => ['array'],
                'utilities.*' => [Rule::enum(LandUtility::class)],
            ],
            options: FieldOptions::LandUtilities,
        );
    }

    public static function guests(): Field
    {
        return self::wholeNumber('guests', 1, 100);
    }

    /**
     * Metres to the sea. The column is the existing distance_to_sea_m.
     */
    public static function distanceToSea(): Field
    {
        return new Field(
            name: 'distance_to_sea_m',
            type: FieldType::Integer,
            required: false,
            rules: fn (array $form): array => [
                'distance_to_sea_m' => ['nullable', 'integer', 'between:0,100000'],
            ],
        );
    }

    public static function checkIn(): Field
    {
        return self::clock('check_in');
    }

    public static function checkOut(): Field
    {
        return self::clock('check_out');
    }

    public static function stars(): Field
    {
        return new Field(
            name: 'stars',
            type: FieldType::Select,
            required: false,
            rules: fn (array $form): array => [
                'stars' => ['nullable', 'integer', 'between:1,5'],
            ],
            options: FieldOptions::Stars,
            showWhen: ShowWhen::equals('vacation_type', VacationType::Hotel->value),
        );
    }

    public static function roomsCount(): Field
    {
        return new Field(
            name: 'rooms_count',
            type: FieldType::Integer,
            required: false,
            rules: fn (array $form): array => [
                'rooms_count' => ['nullable', 'integer', 'between:1,1000'],
            ],
            showWhen: ShowWhen::in('vacation_type', [
                VacationType::Hotel->value,
                VacationType::Guesthouse->value,
                VacationType::Hostel->value,
            ]),
        );
    }

    public static function website(): Field
    {
        return new Field(
            name: 'website',
            type: FieldType::Text,
            required: false,
            rules: fn (array $form): array => [
                'website' => ['nullable', 'url', 'max:255'],
            ],
        );
    }

    private static function wholeNumber(string $name, int $min, int $max): Field
    {
        return new Field(
            name: $name,
            type: FieldType::Integer,
            required: false,
            rules: fn (array $form): array => [
                $name => ['nullable', 'integer', 'between:'.$min.','.$max],
            ],
        );
    }

    private static function clock(string $name): Field
    {
        return new Field(
            name: $name,
            type: FieldType::Text,
            required: false,
            rules: fn (array $form): array => [
                $name => ['nullable', 'date_format:H:i'],
            ],
        );
    }

    /**
     * @return list<Field>
     */
    public static function classifiedType(): array
    {
        return [
            new Field(
                name: 'parent_category_id',
                type: FieldType::Select,
                required: false,
                rules: fn (array $form): array => [],
                options: FieldOptions::ClassifiedParents,
            ),
            new Field(
                name: 'category_id',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'category_id' => ['required', 'integer', Constraints::leafCategory(CategoryModule::Classifieds)],
                ],
                options: FieldOptions::ClassifiedCategories,
            ),
        ];
    }

    /**
     * Category schema, and item condition for goods. Services omit condition.
     *
     * @return list<Field>
     */
    public static function classifiedSpecifics(bool $condition = true): array
    {
        $fields = [];

        if ($condition) {
            $fields[] = new Field(
                name: 'condition',
                type: FieldType::Select,
                required: false,
                rules: fn (array $form): array => [
                    'condition' => ['nullable', Rule::enum(ItemCondition::class)],
                ],
                options: FieldOptions::ItemConditions,
            );
        }

        $fields[] = new Field(
            name: 'attribute_values',
            type: FieldType::Schema,
            required: false,
            rules: fn (array $form): array => Constraints::schemaRules($form),
            options: FieldOptions::CategorySchema,
        );

        return $fields;
    }

    /**
     * @return list<Field>
     */
    public static function vacancyType(): array
    {
        return [
            new Field(
                name: 'category_id',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'category_id' => ['required', 'integer', Constraints::leafCategory(CategoryModule::Vacancies)],
                ],
                options: FieldOptions::VacancyCategories,
            ),
            new Field(
                name: 'employment_type',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'employment_type' => ['required', Rule::enum(EmploymentType::class)],
                ],
                options: FieldOptions::EmploymentTypes,
            ),
        ];
    }

    /**
     * @return list<Field>
     */
    public static function vacancySpecifics(): array
    {
        return [
            new Field(
                name: 'experience',
                type: FieldType::Select,
                required: false,
                rules: fn (array $form): array => [
                    'experience' => ['nullable', Rule::enum(Experience::class)],
                ],
                options: FieldOptions::Experience,
            ),
            new Field(
                name: 'languages',
                type: FieldType::Multiselect,
                required: false,
                rules: fn (array $form): array => [
                    'languages' => ['array'],
                    'languages.*' => [Rule::in(Registry::LANGUAGES)],
                ],
                options: FieldOptions::Languages,
            ),
            new Field(
                name: 'benefits',
                type: FieldType::Multiselect,
                required: false,
                rules: fn (array $form): array => [
                    'benefits' => ['array'],
                    'benefits.*' => [Rule::enum(VacancyBenefit::class)],
                ],
                options: FieldOptions::Benefits,
            ),
            new Field(
                name: 'deadline',
                type: FieldType::Date,
                required: false,
                rules: fn (array $form): array => [
                    'deadline' => ['nullable', 'date', 'after_or_equal:today', 'before:'.now()->addYear()->toDateString()],
                ],
            ),
        ];
    }

    /**
     * @return list<Field>
     */
    public static function location(LocationRules $location): array
    {
        $pin = $location->pinRequired ? 'required' : 'nullable';

        return [
            new Field(
                name: 'location_id',
                type: FieldType::Select,
                required: $location->locationRequired,
                rules: fn (array $form): array => [
                    'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
                ],
                options: FieldOptions::Locations,
            ),
            new Field(
                name: 'address',
                type: FieldType::Text,
                required: false,
                rules: fn (array $form): array => [
                    'address' => ['nullable', 'string', 'max:190'],
                ],
            ),
            new Field(
                name: 'lat',
                type: FieldType::Number,
                required: $location->pinRequired,
                rules: fn (array $form): array => [
                    'lat' => [$pin, 'numeric', 'between:'.$location->bounds['south'].','.$location->bounds['north']],
                ],
            ),
            new Field(
                name: 'lng',
                type: FieldType::Number,
                required: $location->pinRequired,
                rules: fn (array $form): array => [
                    'lng' => [$pin, 'numeric', 'between:'.$location->bounds['west'].','.$location->bounds['east']],
                ],
            ),
        ];
    }

    public static function photos(MediaRules $media): Field
    {
        return new Field(
            name: 'photos',
            type: FieldType::Photos,
            required: $media->required,
            rules: fn (array $form): array => [
                'photos' => [$media->required ? 'required' : 'nullable', 'array', 'max:'.$media->max],
            ],
        );
    }

    /**
     * One amount plus currency and a phone. Classified adds the negotiable flag.
     *
     * @return list<Field>
     */
    public static function fixedPriceAndContact(PriceModel $price, bool $negotiable = false): array
    {
        $presence = $price->required ? 'required' : 'nullable';
        $fields = [
            new Field(
                name: 'price',
                type: FieldType::Number,
                required: $price->required,
                rules: fn (array $form): array => [
                    'price' => [$presence, 'numeric', 'between:'.$price->min.','.$price->max],
                ],
            ),
            new Field(
                name: 'currency',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'currency' => ['required', Rule::enum(Currency::class)],
                ],
                options: FieldOptions::Currencies,
            ),
        ];

        if ($negotiable) {
            $fields[] = new Field(
                name: 'negotiable',
                type: FieldType::Boolean,
                required: false,
                rules: fn (array $form): array => [
                    'negotiable' => ['boolean'],
                ],
            );
        }

        return [
            ...$fields,
            ...self::contact(),
        ];
    }

    /**
     * @return list<Field>
     */
    public static function contact(): array
    {
        return [
            new Field(
                name: 'contact_name',
                type: FieldType::Text,
                required: false,
                rules: fn (array $form): array => [
                    'contact_name' => ['nullable', 'string', 'max:120'],
                ],
            ),
            new Field(
                name: 'contact_phone',
                type: FieldType::Phone,
                required: true,
                rules: fn (array $form): array => [
                    'contact_phone' => self::phone(true),
                ],
            ),
        ];
    }

    /**
     * @return list<Field>
     */
    public static function salaryAndApplication(PriceModel $price): array
    {
        return [
            new Field(
                name: 'salary_min',
                type: FieldType::Integer,
                required: false,
                rules: fn (array $form): array => [
                    'salary_min' => ['nullable', 'integer', 'between:'.$price->min.','.$price->max],
                ],
            ),
            new Field(
                name: 'salary_max',
                type: FieldType::Integer,
                required: false,
                rules: fn (array $form): array => [
                    'salary_max' => ['nullable', 'integer', 'between:'.$price->min.','.$price->max, Constraints::notBelow($form['salary_min'] ?? null)],
                ],
            ),
            new Field(
                name: 'salary_currency',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'salary_currency' => ['required', Rule::enum(Currency::class)],
                ],
                options: FieldOptions::Currencies,
            ),
            new Field(
                name: 'salary_period',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'salary_period' => ['required', Rule::enum(SalaryPeriod::class)],
                ],
                options: FieldOptions::SalaryPeriods,
            ),
            new Field(
                name: 'apply_method',
                type: FieldType::Select,
                required: true,
                rules: fn (array $form): array => [
                    'apply_method' => ['required', Rule::enum(ApplyMethod::class)],
                ],
                options: FieldOptions::ApplyMethods,
            ),
            new Field(
                name: 'apply_phone',
                type: FieldType::Phone,
                required: false,
                rules: fn (array $form): array => [
                    'apply_phone' => self::phone(($form['apply_method'] ?? '') === ApplyMethod::Phone->value),
                ],
                showWhen: ShowWhen::equals('apply_method', ApplyMethod::Phone->value),
            ),
            new Field(
                name: 'apply_email',
                type: FieldType::Email,
                required: false,
                rules: fn (array $form): array => [
                    'apply_email' => [($form['apply_method'] ?? '') === ApplyMethod::Email->value ? 'required' : 'nullable', 'email:rfc', 'max:190'],
                ],
                showWhen: ShowWhen::equals('apply_method', ApplyMethod::Email->value),
            ),
        ];
    }

    /**
     * @return list<string>
     */
    public static function phone(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:24', 'regex:'.Registry::PHONE_PATTERN];
    }
}
