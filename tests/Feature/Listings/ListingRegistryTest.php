<?php

use App\Enums\AmenityScope;
use App\Enums\CategoryModule;
use App\Listings\Field;
use App\Listings\PriceKind;
use App\Listings\Registry;
use App\Listings\ShowWhen;
use App\Models\Amenity;
use App\Models\Category;
use App\Support\Listings\ListingForm;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->travelTo('2026-11-27 12:00:00');
});

/**
 * @param  array<string, mixed>  $rules
 * @return array<string, list<string>>
 */
function listingRuleSignatures(array $rules): array
{
    $signatures = [];

    foreach ($rules as $key => $set) {
        $signatures[$key] = array_map(
            listingRuleSignature(...),
            is_array($set) ? array_values($set) : [$set],
        );
    }

    return $signatures;
}

function listingRuleSignature(mixed $rule): string
{
    if (is_string($rule)) {
        return $rule;
    }

    if ($rule instanceof Stringable) {
        return $rule::class.':'.(string) $rule;
    }

    if ($rule instanceof Closure) {
        $parts = [];

        foreach ((new ReflectionFunction($rule))->getStaticVariables() as $name => $value) {
            $parts[] = $name.'='.match (true) {
                $value instanceof BackedEnum => (string) $value->value,
                $value === null => 'NULL',
                is_scalar($value) => var_export($value, true),
                default => get_debug_type($value),
            };
        }

        return 'closure('.implode(',', $parts).')';
    }

    return get_debug_type($rule);
}

/**
 * @param  array<string, mixed>  $form
 * @return array<string, array<string, list<string>>>
 */
function wizardRuleSignatures(string $module, array $form): array
{
    $signatures = [];

    foreach (ListingForm::STEPS as $step) {
        $signatures[$step] = listingRuleSignatures(ListingForm::rules($module, $step, $form));
    }

    return $signatures;
}

/**
 * @param  array<string, mixed>  $form
 * @return array<string, array<string, list<string>>>
 */
function registryRuleSignatures(string $key, array $form): array
{
    $definition = (new Registry)->get($key);
    $module = match (true) {
        str_starts_with($key, 'property.') => 'property',
        str_starts_with($key, 'classified.') => 'classified',
        default => 'vacancy',
    };
    $signatures = [];

    foreach (ListingForm::STEPS as $step) {
        $prefixed = [];

        foreach ($definition->rulesFor($step, $form) as $name => $rule) {
            $prefixed['form.'.$name] = $rule;
        }

        $signatures[$step] = listingRuleSignatures($prefixed);
    }

    expect((new Registry)->forForm($module, $form)->key())->toBe($key);

    return $signatures;
}

/**
 * Rules captured from ListingForm before the registry (floor and salary left blank).
 *
 * @return array<string, array<string, list<string>>>
 */
function apartmentWizardRules(): array
{
    $enum = 'Illuminate\\Validation\\Rules\\Enum';
    $exists = 'Illuminate\\Validation\\Rules\\Exists';
    $in = 'Illuminate\\Validation\\Rules\\In';

    return [
        'type' => [
            'form.deal' => ['required', $in.':in:"sale","rent"'],
            'form.property_type' => ['required', $in.':in:"apartment","house","land","commercial"'],
        ],
        'details' => [
            'form.title.ka' => ['required', 'string', 'min:5', 'max:120'],
            'form.title.en' => ['nullable', 'string', 'max:120'],
            'form.title.ru' => ['nullable', 'string', 'max:120'],
            'form.description.ka' => ['required', 'string', 'min:20', 'max:5000'],
            'form.description.en' => ['nullable', 'string', 'max:5000'],
            'form.description.ru' => ['nullable', 'string', 'max:5000'],
            'form.area_m2' => ['required', 'numeric', 'between:1,100000'],
            'form.rooms' => ['nullable', 'integer', 'between:1,50'],
            'form.bedrooms' => ['nullable', 'integer', 'between:0,50'],
            'form.bathrooms' => ['nullable', 'integer', 'between:0,20'],
            'form.floor' => ['nullable', 'integer', 'between:-3,100'],
            'form.total_floors' => ['nullable', 'integer', 'between:1,100', "closure(other='')"],
            'form.condition' => ['nullable', $enum.':in:"newly_renovated","old_renovation","needs_renovation","white_frame","black_frame","green_frame"'],
            'form.building_status' => ['nullable', $enum.':in:"new","old","under_construction"'],
            'form.cadastral_code' => ['nullable', 'string', 'max:32', 'regex:/^[\d.\/-]+$/'],
            'form.listed_by' => ['required', $enum.':in:"owner","agency"'],
            'form.features' => ['array'],
            'form.features.*' => [$exists.':exists:amenities,id'],
        ],
        'location' => [
            'form.location_id' => ['required', 'integer', $exists.':exists:locations,id,is_active,"1"'],
            'form.address' => ['nullable', 'string', 'max:190'],
            'form.lat' => ['required', 'numeric', 'between:41.6,41.96'],
            'form.lng' => ['required', 'numeric', 'between:41.62,42.15'],
        ],
        'photos' => [
            'form.photos' => ['required', 'array', 'max:20'],
        ],
        'price' => [
            'form.price' => ['required', 'numeric', 'between:1,100000000000'],
            'form.currency' => ['required', $enum.':in:"GEL","USD","EUR"'],
            'form.contact_name' => ['nullable', 'string', 'max:120'],
            'form.contact_phone' => ['required', 'string', 'max:24', 'regex:/^[\d\s()+-]{9,24}$/'],
        ],
        'preview' => [],
    ];
}

/**
 * @return array<string, array<string, list<string>>>
 */
function classifiedWizardRules(): array
{
    $enum = 'Illuminate\\Validation\\Rules\\Enum';
    $exists = 'Illuminate\\Validation\\Rules\\Exists';

    return [
        'type' => [
            'form.category_id' => ['required', 'integer', 'closure(module=classifieds)'],
        ],
        'details' => [
            'form.title.ka' => ['required', 'string', 'min:5', 'max:120'],
            'form.title.en' => ['nullable', 'string', 'max:120'],
            'form.title.ru' => ['nullable', 'string', 'max:120'],
            'form.description.ka' => ['required', 'string', 'min:20', 'max:5000'],
            'form.description.en' => ['nullable', 'string', 'max:5000'],
            'form.description.ru' => ['nullable', 'string', 'max:5000'],
            'form.condition' => ['nullable', $enum.':in:"new","used"'],
        ],
        'location' => [
            'form.location_id' => ['required', 'integer', $exists.':exists:locations,id,is_active,"1"'],
            'form.address' => ['nullable', 'string', 'max:190'],
            'form.lat' => ['nullable', 'numeric', 'between:41.6,41.96'],
            'form.lng' => ['nullable', 'numeric', 'between:41.62,42.15'],
        ],
        'photos' => [
            'form.photos' => ['nullable', 'array', 'max:20'],
        ],
        'price' => [
            'form.price' => ['nullable', 'numeric', 'between:0,1000000000'],
            'form.currency' => ['required', $enum.':in:"GEL","USD","EUR"'],
            'form.negotiable' => ['boolean'],
            'form.contact_name' => ['nullable', 'string', 'max:120'],
            'form.contact_phone' => ['required', 'string', 'max:24', 'regex:/^[\d\s()+-]{9,24}$/'],
        ],
        'preview' => [],
    ];
}

/**
 * @return array<string, array<string, list<string>>>
 */
function vacancyWizardRules(): array
{
    $enum = 'Illuminate\\Validation\\Rules\\Enum';
    $exists = 'Illuminate\\Validation\\Rules\\Exists';
    $in = 'Illuminate\\Validation\\Rules\\In';

    return [
        'type' => [
            'form.category_id' => ['required', 'integer', 'closure(module=vacancies)'],
            'form.employment_type' => ['required', $enum.':in:"full_time","part_time","seasonal","remote"'],
        ],
        'details' => [
            'form.title.ka' => ['required', 'string', 'min:5', 'max:120'],
            'form.title.en' => ['nullable', 'string', 'max:120'],
            'form.title.ru' => ['nullable', 'string', 'max:120'],
            'form.description.ka' => ['required', 'string', 'min:20', 'max:5000'],
            'form.description.en' => ['nullable', 'string', 'max:5000'],
            'form.description.ru' => ['nullable', 'string', 'max:5000'],
            'form.experience' => ['nullable', $enum.':in:"none","under_1","1_3","3_5","over_5"'],
            'form.languages' => ['array'],
            'form.languages.*' => [$in.':in:"ka","en","ru","tr","de"'],
            'form.benefits' => ['array'],
            'form.benefits.*' => [$enum.':in:"accommodation","meals","transport","uniform","training"'],
            'form.deadline' => ['nullable', 'date', 'after_or_equal:today', 'before:2027-11-27'],
        ],
        'location' => [
            'form.location_id' => ['required', 'integer', $exists.':exists:locations,id,is_active,"1"'],
            'form.address' => ['nullable', 'string', 'max:190'],
            'form.lat' => ['nullable', 'numeric', 'between:41.6,41.96'],
            'form.lng' => ['nullable', 'numeric', 'between:41.62,42.15'],
        ],
        'photos' => [
            'form.photos' => ['nullable', 'array', 'max:20'],
        ],
        'price' => [
            'form.salary_min' => ['nullable', 'integer', 'between:0,1000000'],
            'form.salary_max' => ['nullable', 'integer', 'between:0,1000000', "closure(other='')"],
            'form.salary_currency' => ['required', $enum.':in:"GEL","USD","EUR"'],
            'form.salary_period' => ['required', $enum.':in:"hour","day","month","season"'],
            'form.apply_method' => ['required', $enum.':in:"site","phone","email"'],
            'form.apply_phone' => ['nullable', 'string', 'max:24', 'regex:/^[\d\s()+-]{9,24}$/'],
            'form.apply_email' => ['nullable', 'email:rfc', 'max:190'],
        ],
        'preview' => [],
    ];
}

it('registers one definition per listing kind', function () {
    expect((new Registry)->keys())->toBe([
        'property.apartment',
        'property.house',
        'property.land',
        'property.commercial',
        'property.vacation',
        'classified.goods',
        'classified.service',
        'vacancy',
    ]);
});

it('keeps the wizard steps in the current order', function (string $key) {
    $steps = (new Registry)->get($key)->steps();

    expect(array_map(fn ($step) => $step->key, $steps))->toBe(Registry::STEPS)
        ->and(array_map(fn ($step) => $step->order, $steps))->toBe([1, 2, 3, 4, 5, 6]);
})->with([
    'apartment' => 'property.apartment',
    'house' => 'property.house',
    'land' => 'property.land',
    'commercial' => 'property.commercial',
    'vacation' => 'property.vacation',
    'goods' => 'classified.goods',
    'service' => 'classified.service',
    'vacancy' => 'vacancy',
]);

it('declares the fields the wizard edits, in validation order', function (string $key, array $fields) {
    $definition = (new Registry)->get($key);
    $actual = [];

    foreach ($definition->steps() as $step) {
        $actual[$step->key] = $step->fieldNames();
    }

    expect($actual)->toBe($fields);
})->with([
    'apartment' => ['property.apartment', apartmentFields()],
    'house' => ['property.house', houseFields()],
    'land' => ['property.land', landFields()],
    'commercial' => ['property.commercial', commercialFields()],
    'vacation' => ['property.vacation', vacationFields()],
    'goods' => ['classified.goods', classifiedFields(true)],
    'service' => ['classified.service', classifiedFields(false)],
    'vacancy' => ['vacancy', vacancyFields()],
]);

it('matches the pre-refactor rules for an apartment', function () {
    $form = ['property_type' => 'apartment', 'floor' => ''];

    expect(wizardRuleSignatures('property', $form))->toBe(apartmentWizardRules())
        ->and(registryRuleSignatures('property.apartment', $form))->toBe(apartmentWizardRules());
});

it('gives a house the apartment facts plus yard area and no floor of the building', function () {
    $form = ['property_type' => 'house', 'floor' => ''];
    $details = wizardRuleSignatures('property', $form)['details'];

    expect(array_keys($details))->toBe([
        'form.title.ka', 'form.title.en', 'form.title.ru',
        'form.description.ka', 'form.description.en', 'form.description.ru',
        'form.area_m2', 'form.land_area_m2', 'form.rooms', 'form.bedrooms', 'form.bathrooms',
        'form.total_floors', 'form.condition', 'form.building_status', 'form.cadastral_code', 'form.listed_by',
        'form.features', 'form.features.*',
    ])
        ->and($details['form.land_area_m2'])->toBe(['nullable', 'numeric', 'between:1,10000000'])
        ->and($details)->not->toHaveKey('form.floor')
        ->and(registryRuleSignatures('property.house', $form)['details'])->toBe($details);
});

it('requires a commercial subtype and keeps only area and floors', function () {
    $enum = 'Illuminate\\Validation\\Rules\\Enum';
    $form = ['property_type' => 'commercial', 'floor' => ''];
    $rules = wizardRuleSignatures('property', $form);

    expect(array_keys($rules['type']))->toBe(['form.deal', 'form.property_type', 'form.commercial_subtype'])
        ->and($rules['type']['form.commercial_subtype'])->toBe(['required', $enum.':in:"office","shop","warehouse","hotel_business"'])
        ->and(array_keys($rules['details']))->toBe([
            'form.title.ka', 'form.title.en', 'form.title.ru',
            'form.description.ka', 'form.description.en', 'form.description.ru',
            'form.area_m2', 'form.floor', 'form.total_floors',
        ])
        ->and($rules['details']['form.area_m2'][0])->toBe('required')
        ->and(registryRuleSignatures('property.commercial', $form))->toBe($rules);
});

it('reads a leftover hotel-as-a-business type as commercial', function () {
    expect((new Registry)->forForm('property', ['property_type' => 'hotel_business'])->key())->toBe('property.commercial');
});

it('keeps an unknown property type on the apartment rules', function () {
    $form = ['property_type' => 'nope', 'floor' => ''];

    expect(wizardRuleSignatures('property', $form))->toBe(apartmentWizardRules())
        ->and((new Registry)->forForm('property', $form)->key())->toBe('property.apartment');
});

it('asks land only for plot size, purpose and utilities', function () {
    $enum = 'Illuminate\\Validation\\Rules\\Enum';
    $form = ['property_type' => 'land'];
    $details = wizardRuleSignatures('property', $form)['details'];

    expect(array_keys($details))->toBe([
        'form.title.ka', 'form.title.en', 'form.title.ru',
        'form.description.ka', 'form.description.en', 'form.description.ru',
        'form.land_area_m2', 'form.purpose', 'form.utilities', 'form.utilities.*',
    ])
        ->and($details['form.land_area_m2'])->toBe(['required', 'numeric', 'between:1,10000000'])
        ->and($details['form.purpose'])->toBe(['required', $enum.':in:"residential","agricultural","commercial"'])
        ->and($details['form.utilities'])->toBe(['array'])
        ->and($details['form.utilities.*'])->toBe([$enum.':in:"water","electricity","gas","road_access"'])
        ->and(registryRuleSignatures('property.land', $form)['details'])->toBe($details);
});

it('treats vacation as daily rent and shows stars and room count only for the stays that use them', function () {
    $enum = 'Illuminate\\Validation\\Rules\\Enum';
    $form = ['rubric' => 'vacation', 'vacation_type' => 'apartment'];
    $rules = wizardRuleSignatures('property', $form);
    $stars = collect((new Registry)->get('property.vacation')->step('details')->fields)->first(fn (Field $field): bool => $field->name === 'stars');
    $rooms = collect((new Registry)->get('property.vacation')->step('details')->fields)->first(fn (Field $field): bool => $field->name === 'rooms_count');

    expect($rules['type'])->toBe([
        'form.vacation_type' => ['required', $enum.':in:"hotel","guesthouse","apartment","house","hostel"'],
    ])
        ->and(array_keys($rules['details']))->toContain('form.guests', 'form.bedrooms', 'form.distance_to_sea_m', 'form.check_in', 'form.check_out', 'form.stars', 'form.rooms_count', 'form.features')
        ->and($rules['details'])->not->toHaveKey('form.rooms')
        ->and($rules['price'])->toHaveKey('form.website')
        ->and($rules['price'])->not->toHaveKey('form.deal')
        ->and($stars->visible(['vacation_type' => 'hotel']))->toBeTrue()
        ->and($stars->visible(['vacation_type' => 'apartment']))->toBeFalse()
        ->and($rooms->visible(['vacation_type' => 'hostel']))->toBeTrue()
        ->and($rooms->visible(['vacation_type' => 'guesthouse']))->toBeTrue()
        ->and($rooms->visible(['vacation_type' => 'house']))->toBeFalse()
        ->and((new Registry)->forForm('property', ['property_type' => 'hotel', 'deal' => 'daily_rent'])->key())->toBe('property.vacation');
});

it('accepts a hotel amenity only on a vacation listing', function () {
    $hotelAmenity = Amenity::factory()->scope(AmenityScope::Hotel)->create();
    $propertyAmenity = Amenity::factory()->scope(AmenityScope::Property)->create();

    $hotelForm = ['rubric' => 'vacation', 'vacation_type' => 'hotel', 'features' => [(string) $hotelAmenity->id, (string) $propertyAmenity->id]];
    $apartmentForm = ['property_type' => 'apartment', 'features' => [(string) $hotelAmenity->id]];

    expect(Validator::make(['form' => $hotelForm], ListingForm::rules('property', 'details', $hotelForm))->errors()->has('form.features.0'))->toBeFalse()
        ->and(Validator::make(['form' => $hotelForm], ListingForm::rules('property', 'details', $hotelForm))->errors()->has('form.features.1'))->toBeFalse()
        ->and(Validator::make(['form' => $apartmentForm], ListingForm::rules('property', 'details', $apartmentForm))->errors()->has('form.features.0'))->toBeTrue();
});

it('matches the pre-refactor rules for goods and services', function () {
    $form = ['category_id' => ''];

    expect(wizardRuleSignatures('classified', $form))->toBe(classifiedWizardRules())
        ->and(registryRuleSignatures('classified.goods', $form))->toBe(classifiedWizardRules());
});

it('selects the service definition for a services category and drops item condition', function () {
    $services = Category::factory()->create([
        'module' => CategoryModule::Classifieds,
        'parent_id' => null,
        'slug' => 'services',
    ]);
    $cleaning = Category::factory()->create([
        'module' => CategoryModule::Classifieds,
        'parent_id' => $services->id,
        'slug' => 'cleaning',
        'attribute_schema' => [
            ['key' => 'hourly', 'type' => 'boolean', 'label' => ['ka' => 'საათობრივი'], 'required' => true],
        ],
    ]);
    $goods = Category::factory()->create([
        'module' => CategoryModule::Classifieds,
        'parent_id' => null,
        'slug' => 'furniture',
    ]);

    $serviceForm = ['category_id' => (string) $cleaning->id];
    $goodsForm = ['category_id' => (string) $goods->id];

    expect((new Registry)->forForm('classified', $serviceForm)->key())->toBe('classified.service')
        ->and((new Registry)->forForm('classified', $goodsForm)->key())->toBe('classified.goods')
        ->and((new Registry)->forForm('classified', ['category_id' => (string) $services->id])->key())->toBe('classified.service')
        ->and(array_keys(ListingForm::rules('classified', 'details', $serviceForm)))->toContain('form.attribute_values.hourly')
        ->and(array_keys(ListingForm::rules('classified', 'details', $serviceForm)))->not->toContain('form.condition')
        ->and(array_keys(ListingForm::rules('classified', 'details', $goodsForm)))->toContain('form.condition')
        ->and(array_keys(ListingForm::rules('classified', 'details', $goodsForm)))->not->toContain('form.attribute_values.hourly');
});

it('matches the pre-refactor rules for a vacancy', function () {
    $form = ['apply_method' => 'site', 'salary_min' => ''];

    expect(wizardRuleSignatures('vacancy', $form))->toBe(vacancyWizardRules())
        ->and(registryRuleSignatures('vacancy', $form))->toBe(vacancyWizardRules());
});

it('requires the apply phone or email only for that method', function (string $method, string $phone, string $email) {
    $rules = listingRuleSignatures(ListingForm::rules('vacancy', 'price', ['apply_method' => $method, 'salary_min' => '10']));

    expect($rules['form.apply_phone'][0])->toBe($phone)
        ->and($rules['form.apply_email'][0])->toBe($email)
        ->and($rules['form.salary_max'])->toContain("closure(other='10')");
})->with([
    'site' => ['site', 'nullable', 'nullable'],
    'phone' => ['phone', 'required', 'nullable'],
    'email' => ['email', 'nullable', 'required'],
]);

it('hides the apply inputs until that method is chosen and still validates them', function () {
    $fields = (new Registry)->get('vacancy')->step('price')->fields;
    $phone = collect($fields)->first(fn (Field $field): bool => $field->name === 'apply_phone');
    $email = collect($fields)->first(fn (Field $field): bool => $field->name === 'apply_email');

    expect($phone)->not->toBeNull()
        ->and($email)->not->toBeNull()
        ->and($phone->showWhen)->toEqual(ShowWhen::equals('apply_method', 'phone'))
        ->and($email->showWhen)->toEqual(ShowWhen::equals('apply_method', 'email'))
        ->and($phone->visible(['apply_method' => 'site']))->toBeFalse()
        ->and($phone->visible(['apply_method' => 'phone']))->toBeTrue()
        ->and(array_key_exists('apply_phone', (new Registry)->get('vacancy')->rulesFor('price', ['apply_method' => 'site'])))->toBeTrue();
});

it('describes price, place and photos the way the wizard enforces them', function (string $key, PriceKind $kind, bool $priceRequired, int $min, int $max, bool $perSquareMeter, bool $pinRequired, bool $photosRequired) {
    $definition = (new Registry)->get($key);

    expect($definition->price()->kind)->toBe($kind)
        ->and($definition->price()->required)->toBe($priceRequired)
        ->and($definition->price()->min)->toBe($min)
        ->and($definition->price()->max)->toBe($max)
        ->and($definition->price()->currencies)->toBe(['GEL', 'USD', 'EUR'])
        ->and($definition->price()->perSquareMeter)->toBe($perSquareMeter)
        ->and($definition->location()->pinRequired)->toBe($pinRequired)
        ->and($definition->location()->locationRequired)->toBeTrue()
        ->and($definition->location()->bounds)->toBe(Registry::BOUNDS)
        ->and($definition->media()->required)->toBe($photosRequired)
        ->and($definition->media()->min)->toBe($photosRequired ? 1 : 0)
        ->and($definition->media()->max)->toBe(20)
        ->and(ListingForm::requiresPin(strtok($key, '.')))->toBe($pinRequired)
        ->and(ListingForm::requiresPhotos(strtok($key, '.')))->toBe($photosRequired);
})->with([
    'apartment' => ['property.apartment', PriceKind::Fixed, true, 1, 100000000000, true, true, true],
    'land' => ['property.land', PriceKind::Fixed, true, 1, 100000000000, true, true, true],
    'vacation' => ['property.vacation', PriceKind::Fixed, true, 1, 100000000000, false, true, true],
    'goods' => ['classified.goods', PriceKind::OptionalQuote, false, 0, 1000000000, false, false, false],
    'service' => ['classified.service', PriceKind::OptionalQuote, false, 0, 1000000000, false, false, false],
    'vacancy' => ['vacancy', PriceKind::SalaryRange, false, 0, 1000000, false, false, false],
]);

it('rejects a floor count below the entered floor with the current message', function () {
    app()->setLocale('en');
    $form = ['property_type' => 'apartment', 'floor' => '5', 'total_floors' => '2'];

    expect(Validator::make(['form' => $form], ListingForm::rules('property', 'details', $form))->errors()->first('form.total_floors'))
        ->toBe('This value cannot be lower than the previous one.');
});

it('rejects a category that is not an active leaf with the current message', function () {
    app()->setLocale('en');
    $parent = Category::factory()->create(['module' => CategoryModule::Classifieds, 'parent_id' => null]);
    Category::factory()->create(['module' => CategoryModule::Classifieds, 'parent_id' => $parent->id]);
    $form = ['category_id' => (string) $parent->id];

    expect(Validator::make(['form' => $form], ListingForm::rules('classified', 'type', $form))->errors()->first('form.category_id'))
        ->toBe('Choose the most specific category.');
});

it('sends an unknown module back to the type step', function () {
    expect(array_keys(ListingForm::rules('nope', 'type', [])))->toBe(['form.type'])
        ->and(ListingForm::rules('nope', 'details', []))->toBe([])
        ->and(ListingForm::stepOf('property', 'form.price', ['property_type' => 'apartment']))->toBe('price')
        ->and(ListingForm::stepOf('property', 'form.land_area_m2', ['property_type' => 'land']))->toBe('details');
});

/**
 * @return array<string, list<string>>
 */
function apartmentFields(): array
{
    return propertyShape([
        'type' => ['deal', 'property_type'],
        'details' => ['title', 'description', 'area_m2', 'rooms', 'bedrooms', 'bathrooms', 'floor', 'total_floors', 'condition', 'building_status', 'cadastral_code', 'listed_by', 'features'],
    ]);
}

/**
 * @return array<string, list<string>>
 */
function houseFields(): array
{
    return propertyShape([
        'type' => ['deal', 'property_type'],
        'details' => ['title', 'description', 'area_m2', 'land_area_m2', 'rooms', 'bedrooms', 'bathrooms', 'total_floors', 'condition', 'building_status', 'cadastral_code', 'listed_by', 'features'],
    ]);
}

/**
 * @return array<string, list<string>>
 */
function landFields(): array
{
    return propertyShape([
        'type' => ['deal', 'property_type'],
        'details' => ['title', 'description', 'land_area_m2', 'purpose', 'utilities'],
    ]);
}

/**
 * @return array<string, list<string>>
 */
function commercialFields(): array
{
    return propertyShape([
        'type' => ['deal', 'property_type', 'commercial_subtype'],
        'details' => ['title', 'description', 'area_m2', 'floor', 'total_floors'],
    ]);
}

/**
 * @return array<string, list<string>>
 */
function vacationFields(): array
{
    return propertyShape([
        'type' => ['vacation_type'],
        'details' => ['title', 'description', 'guests', 'bedrooms', 'distance_to_sea_m', 'check_in', 'check_out', 'stars', 'rooms_count', 'features'],
        'price' => ['price', 'currency', 'contact_name', 'contact_phone', 'website'],
    ]);
}

/**
 * @param  array<string, list<string>>  $overrides
 * @return array<string, list<string>>
 */
function propertyShape(array $overrides): array
{
    return array_replace([
        'type' => [],
        'details' => [],
        'location' => ['location_id', 'address', 'lat', 'lng'],
        'photos' => ['photos'],
        'price' => ['price', 'currency', 'contact_name', 'contact_phone'],
        'preview' => [],
    ], $overrides);
}

/**
 * @return array<string, list<string>>
 */
function classifiedFields(bool $condition = true): array
{
    return [
        'type' => ['parent_category_id', 'category_id'],
        'details' => $condition
            ? ['title', 'description', 'condition', 'attribute_values']
            : ['title', 'description', 'attribute_values'],
        'location' => ['location_id', 'address', 'lat', 'lng'],
        'photos' => ['photos'],
        'price' => ['price', 'currency', 'negotiable', 'contact_name', 'contact_phone'],
        'preview' => [],
    ];
}

/**
 * @return array<string, list<string>>
 */
function vacancyFields(): array
{
    return [
        'type' => ['category_id', 'employment_type'],
        'details' => ['title', 'description', 'experience', 'languages', 'benefits', 'deadline'],
        'location' => ['location_id', 'address', 'lat', 'lng'],
        'photos' => ['photos'],
        'price' => ['salary_min', 'salary_max', 'salary_currency', 'salary_period', 'apply_method', 'apply_phone', 'apply_email'],
        'preview' => [],
    ];
}
