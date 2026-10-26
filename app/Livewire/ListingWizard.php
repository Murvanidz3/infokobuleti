<?php

namespace App\Livewire;

use App\Actions\Listings\SubmitListing;
use App\Contracts\Geocoder;
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
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SalaryPeriod;
use App\Enums\VacancyBenefit;
use App\Exceptions\ListingLimitReached;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\ListingDraft;
use App\Models\Location;
use App\Models\User;
use App\Services\Account\OwnerListings;
use App\Services\DateFormatter;
use App\Services\Listings\ListingLimits;
use App\Services\Media\ImageSanitizer;
use App\Services\PriceFormatter;
use App\Support\AttributeSchema;
use App\Support\Geo\SeaDistance;
use App\Support\Listings\ListingForm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The add-listing wizard (/add) and listing editor: module and category, details, location,
 * photos, price and contact, preview. Every change is autosaved to a ListingDraft (?draft= in the
 * URL), so a refresh or a closed tab loses nothing; the listing itself changes only on submit.
 */
class ListingWizard extends Component
{
    use WithFileUploads;

    public const int UPLOAD_MAX_KB = 15360;

    #[Url(as: 'draft', except: 0)]
    public int $draftId = 0;

    /** Morph alias of the listing being edited (null for a new listing). */
    #[Locked]
    public ?string $editType = null;

    #[Locked]
    public ?int $editId = null;

    #[Locked]
    public string $type = '';

    public int $step = 1;

    /** Furthest step reached: the progress bar lets the user jump back up to here. */
    #[Locked]
    public int $reached = 1;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $addressQuery = '';

    /** @var list<array{label: string, lat: float, lng: float}> */
    public array $addressResults = [];

    public function mount(OwnerListings $listings, ?string $editType = null, ?int $editId = null): void
    {
        $user = $this->user();

        if ($editType !== null && $editId !== null) {
            $listing = $listings->find($user, $editType, $editId);
            abort_unless(Gate::forUser($user)->allows('update', $listing), 403);

            $this->editType = $editType;
            $this->editId = $editId;
            $this->type = $editType;

            $draft = ListingDraft::query()->firstOrNew(['user_id' => $user->id, 'listing_type' => $editType, 'listing_id' => $editId]);

            if (! $draft->exists) {
                $listing->loadMissing('media');
                $form = ListingForm::fromListing($listing);
                $form['photos'] = $listing->getMedia('gallery')->map(fn (Media $media): array => $this->photo($media, 'listing'))->values()->all();
                $draft->fill(['step' => 2, 'data' => $form])->save();
            }

            $this->load($draft);
            $this->reached = count(ListingForm::STEPS);

            return;
        }

        if ($this->draftId > 0) {
            $draft = ListingDraft::query()->where('user_id', $user->id)->whereNull('listing_id')->find($this->draftId);

            if ($draft !== null) {
                $this->load($draft);

                return;
            }

            $this->draftId = 0;
        }
    }

    public function chooseType(string $type): void
    {
        abort_unless(in_array($type, ListingForm::TYPES, true), 404);

        if ($this->editType !== null || $type === $this->type) {
            return;
        }

        $this->type = $type;
        $this->form = ListingForm::defaults($type, $this->user());
        $this->resetValidation();
        $this->persist();
    }

    public function updated(string $property, mixed $value): void
    {
        if (! str_starts_with($property, 'form.')) {
            return;
        }

        // Category changes reset what depends on them.
        if ($property === 'form.parent_category_id') {
            $children = Category::query()->where('parent_id', (int) $value)->where('is_active', true)->exists();
            $this->form['category_id'] = $children ? '' : (string) $value;
            $this->form['attribute_values'] = [];
        }

        if ($property === 'form.category_id' && $this->type === 'classified') {
            $this->form['attribute_values'] = [];
        }

        $this->persist();
    }

    public function next(): void
    {
        $this->validateStep(ListingForm::STEPS[$this->step - 1]);

        if ($this->step < count(ListingForm::STEPS)) {
            $this->step++;
            $this->reached = max($this->reached, $this->step);
        }

        $this->persist();
        $this->dispatch('wizard-step', step: $this->step);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
        $this->persist();
        $this->dispatch('wizard-step', step: $this->step);
    }

    public function goTo(int $step): void
    {
        if ($step >= 1 && $step <= $this->reached && $this->type !== '') {
            $this->step = $step;
            $this->persist();
            $this->dispatch('wizard-step', step: $this->step);
        }
    }

    public function updatedUpload(ImageSanitizer $sanitizer): void
    {
        $photos = $this->form['photos'] ?? [];

        if (count($photos) >= ListingForm::MAX_PHOTOS) {
            $this->reset('upload');
            $this->addError('upload', __('account.wizard.photos.limit', ['max' => ListingForm::MAX_PHOTOS]));

            return;
        }

        $this->validate(['upload' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.self::UPLOAD_MAX_KB]], [], ['upload' => __('account.wizard.photos.photo')]);

        /** @var TemporaryUploadedFile $file */
        $file = $this->upload;

        try {
            $mime = $sanitizer->sanitize($file->getRealPath());
        } catch (RuntimeException) {
            $this->reset('upload');
            $this->addError('upload', __('account.wizard.photos.broken'));

            return;
        }

        $draft = $this->persist();
        $extension = ['image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';

        $media = $draft->addMedia($file->getRealPath())
            ->usingFileName(bin2hex(random_bytes(8)).'.'.$extension)
            ->toMediaCollection('photos');

        $photos[] = $this->photo($media, 'draft');
        $this->form['photos'] = $photos;
        $this->reset('upload');
        $this->resetErrorBag('form.photos');
        $this->persist();
    }

    public function removePhoto(int $index): void
    {
        $photos = $this->form['photos'] ?? [];
        $photo = $photos[$index] ?? null;

        if ($photo === null) {
            return;
        }

        if ($photo['source'] === 'draft') {
            $this->draft()?->media()->whereKey((int) $photo['id'])->first()?->delete();
        }

        array_splice($photos, $index, 1);
        $this->form['photos'] = array_values($photos);
        $this->persist();
    }

    public function movePhoto(int $from, int $to): void
    {
        $photos = $this->form['photos'] ?? [];

        if (! isset($photos[$from]) || $to < 0 || $to >= count($photos)) {
            return;
        }

        $moved = array_splice($photos, $from, 1);
        array_splice($photos, $to, 0, $moved);
        $this->form['photos'] = array_values($photos);
        $this->persist();
    }

    public function searchAddress(Geocoder $geocoder): void
    {
        $key = 'geocode-user:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->addError('addressQuery', __('account.wizard.location.too_many'));

            return;
        }

        RateLimiter::hit($key, 60);

        $this->addressResults = $geocoder->search($this->addressQuery, app()->getLocale());

        if ($this->addressResults === []) {
            $this->addError('addressQuery', __('account.wizard.location.not_found'));
        }
    }

    public function pickAddress(int $index): void
    {
        $result = $this->addressResults[$index] ?? null;

        if ($result === null) {
            return;
        }

        if (blank($this->form['address'] ?? null)) {
            $this->form['address'] = mb_substr($result['label'], 0, 190);
        }

        $this->addressResults = [];
        $this->setPin($result['lat'], $result['lng']);
        $this->dispatch('pin-moved', lat: $result['lat'], lng: $result['lng']);
    }

    public function setPin(float $lat, float $lng): void
    {
        $bounds = ListingForm::BOUNDS;

        if ($lat < $bounds['south'] || $lat > $bounds['north'] || $lng < $bounds['west'] || $lng > $bounds['east']) {
            $this->addError('form.lat', __('account.wizard.location.outside'));

            return;
        }

        $this->form['lat'] = round($lat, 7);
        $this->form['lng'] = round($lng, 7);
        $this->resetErrorBag(['form.lat', 'form.lng']);

        if (blank($this->form['location_id'] ?? null)) {
            $this->form['location_id'] = (string) ($this->nearestLocation($lat, $lng) ?? '');
        }

        $this->persist();
    }

    public function submit(SubmitListing $submit): void
    {
        $draft = $this->persist();

        try {
            $listing = $submit->handle($this->user(), $draft);
        } catch (ValidationException $exception) {
            $first = (string) array_key_first($exception->errors());
            $this->step = array_search(ListingForm::stepOf($this->type, $first, $this->form), ListingForm::STEPS, true) + 1;
            $this->persist();

            throw $exception;
        } catch (ListingLimitReached $exception) {
            $this->addError('limit', __($exception->package ? 'billing.package.limit_reached' : 'account.wizard.limit_reached', ['count' => $exception->limit]));

            return;
        }

        session()->flash('status', $this->editType !== null ? __('account.wizard.updated') : __('account.wizard.submitted'));

        $this->redirect(localized_route('account.listings', ['type' => $listing->getMorphClass(), 'group' => 'pending']));
    }

    public function discard(): void
    {
        $this->draft()?->delete();

        $this->redirect($this->editType !== null ? localized_route('account.listings', ['type' => $this->editType]) : localized_route('listings.create'));
    }

    public function render(OwnerListings $listings, ListingLimits $limits, PriceFormatter $price): View
    {
        $user = $this->user();

        return view('livewire.listing-wizard', [
            'steps' => ListingForm::STEPS,
            'current' => ListingForm::STEPS[$this->step - 1],
            'drafts' => $this->type === '' ? $this->openDrafts() : [],
            'remaining' => $this->editType === null ? $limits->remaining($user) : null,
            'options' => $this->type !== '' ? $this->options() : [],
            'schemaFields' => $this->type === 'classified' ? $this->schemaFields() : [],
            'sea' => SeaDistance::meters(is_numeric($this->form['lat'] ?? null) ? (float) $this->form['lat'] : null, is_numeric($this->form['lng'] ?? null) ? (float) $this->form['lng'] : null),
            'summary' => ListingForm::STEPS[$this->step - 1] === 'preview' ? $this->summary($price) : [],
            'editing' => $this->editType !== null,
            'cancelUrl' => $this->editType !== null ? localized_route('account.listings', ['type' => $this->editType]) : localized_route('account'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validationAttributes(): array
    {
        return ListingForm::validationAttributes();
    }

    private function validateStep(string $step): void
    {
        if ($step === 'type' && $this->type === '') {
            $this->addError('type', __('account.wizard.choose_type'));

            throw ValidationException::withMessages(['type' => __('account.wizard.choose_type')]);
        }

        $rules = ListingForm::rules($this->type, $step, $this->form);

        if ($rules !== []) {
            $this->validate($rules, [], ListingForm::validationAttributes());
        }
    }

    /**
     * Saves the wizard state, creating the draft on first use.
     */
    private function persist(): ListingDraft
    {
        $draft = $this->draft() ?? new ListingDraft(['user_id' => $this->user()->id, 'listing_type' => $this->editType, 'listing_id' => $this->editId]);

        $draft->fill([
            'listing_type' => $this->type !== '' ? $this->type : null,
            'step' => $this->step,
            'data' => [...$this->form, '_reached' => $this->reached],
        ]);
        $draft->save();

        if ($this->editType === null) {
            $this->draftId = $draft->id;
        }

        return $draft;
    }

    private function draft(): ?ListingDraft
    {
        $query = ListingDraft::query()->where('user_id', $this->user()->id);

        if ($this->editType !== null) {
            return $query->where('listing_type', $this->editType)->where('listing_id', $this->editId)->first();
        }

        return $this->draftId > 0 ? $query->whereNull('listing_id')->find($this->draftId) : null;
    }

    private function load(ListingDraft $draft): void
    {
        $data = $draft->data;
        $this->reached = max(1, min(count(ListingForm::STEPS), (int) ($data['_reached'] ?? $draft->step)));
        unset($data['_reached']);

        $this->type = (string) $draft->listing_type;
        $this->form = $this->type !== '' ? array_replace(ListingForm::defaults($this->type, $this->user()), $data) : [];
        $this->step = $this->type !== '' ? max(1, min($this->reached, $draft->step)) : 1;

        if ($this->editType === null) {
            $this->draftId = $draft->id;
        }
    }

    /**
     * @return array{source: string, id: int, url: string}
     */
    private function photo(Media $media, string $source): array
    {
        return [
            'source' => $source,
            'id' => (int) $media->getKey(),
            'url' => $source === 'listing' && $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
        ];
    }

    /**
     * New-listing drafts the user can continue.
     *
     * @return list<array{id: int, title: string, type: string, updated: string}>
     */
    private function openDrafts(): array
    {
        return ListingDraft::query()
            ->where('user_id', $this->user()->id)
            ->whereNull('listing_id')
            ->whereNotNull('listing_type')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (ListingDraft $draft): array => [
                'id' => $draft->id,
                'title' => trim((string) ($draft->data['title']['ka'] ?? '')) ?: __('account.listings.untitled'),
                'type' => __('account.types.'.$draft->listing_type),
                'updated' => app(DateFormatter::class)->relative($draft->updated_at ?? now()),
            ])
            ->all();
    }

    /**
     * Select options for the chosen module.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        $locations = Location::query()->active()->whereNotNull('parent_id')->orderBy('sort')->get()
            ->mapWithKeys(fn (Location $location): array => [(string) $location->id => (string) $location->name])->all();

        $common = [
            'locations' => $locations,
            'currencies' => collect(Currency::cases())->mapWithKeys(fn (Currency $currency): array => [$currency->value => $currency->value])->all(),
        ];

        return $common + match ($this->type) {
            'hotel' => [
                'hotelTypes' => HotelType::options(),
                'amenities' => $this->amenities(AmenityScope::Hotel),
                'months' => collect(range(1, 12))->mapWithKeys(fn (int $month): array => [(string) $month => now()->setDate(2026, $month, 1)->locale(app()->getLocale())->translatedFormat('F')])->all(),
            ],
            'property' => [
                'deals' => PropertyDeal::options(),
                'propertyTypes' => PropertyType::options(),
                'conditions' => PropertyCondition::options(),
                'buildingStatuses' => BuildingStatus::options(),
                'listedBy' => ListedBy::options(),
                'features' => $this->amenities(AmenityScope::Property),
            ],
            'classified' => [
                'parents' => $this->categories(CategoryModule::Classifieds, null),
                'children' => filled($this->form['parent_category_id'] ?? null) ? $this->categories(CategoryModule::Classifieds, (int) $this->form['parent_category_id']) : [],
                'conditions' => ItemCondition::options(),
            ],
            'vacancy' => [
                'categories' => $this->categories(CategoryModule::Vacancies, null),
                'employmentTypes' => EmploymentType::options(),
                'experience' => Experience::options(),
                'languages' => collect(ListingForm::LANGUAGES)->mapWithKeys(fn (string $code): array => [$code => __('site.languages.'.$code)])->all(),
                'benefits' => VacancyBenefit::options(),
                'periods' => SalaryPeriod::options(),
                'applyMethods' => ApplyMethod::options(),
            ],
            default => [],
        };
    }

    /**
     * @return array<int, array{name: string, icon: ?string}>
     */
    private function categories(CategoryModule $module, ?int $parent): array
    {
        return Category::query()
            ->where('module', $module->value)
            ->where('is_active', true)
            ->when($parent === null, fn (Builder $query) => $query->whereNull('parent_id'), fn (Builder $query) => $query->where('parent_id', $parent))
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [(string) $category->id => ['name' => (string) $category->name, 'icon' => $category->icon]])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function amenities(AmenityScope $scope): array
    {
        return Amenity::query()->where('scope', $scope->value)->orderBy('sort')->get()
            ->mapWithKeys(fn (Amenity $amenity): array => [(string) $amenity->id => (string) $amenity->name])->all();
    }

    /**
     * Fields of the chosen classified category's schema, with labels and choices in this language.
     *
     * @return list<array{key: string, type: string, label: string, choices: array<string, string>, required: bool, unit: ?string}>
     */
    private function schemaFields(): array
    {
        $category = Category::query()->find(is_numeric($this->form['category_id'] ?? null) ? (int) $this->form['category_id'] : null);

        if ($category === null) {
            return [];
        }

        return array_map(fn (array $field): array => [
            'key' => $field['key'],
            'type' => $field['type'],
            'label' => AttributeSchema::label($field),
            'choices' => AttributeSchema::choices($field),
            'required' => (bool) ($field['required'] ?? false),
            'unit' => $field['unit'] ?? null,
        ], $category->schema()->fields());
    }

    private function nearestLocation(float $lat, float $lng): ?int
    {
        $nearest = null;
        $best = INF;

        foreach (Location::query()->active()->whereNotNull('parent_id')->whereNotNull('lat')->get(['id', 'lat', 'lng']) as $location) {
            $distance = hypot(((float) $location->lat - $lat) * 111.0, ((float) $location->lng - $lng) * 111.0 * cos(deg2rad($lat)));

            if ($distance < $best) {
                [$best, $nearest] = [$distance, (int) $location->id];
            }
        }

        return $best <= 6 ? $nearest : null;
    }

    /**
     * Key facts for the preview step.
     *
     * @return list<array{label: string, value: string}>
     */
    private function summary(PriceFormatter $price): array
    {
        $form = $this->form;
        $options = $this->options();
        $currency = fn (string $key): string => in_array($form[$key] ?? '', Currency::values(), true) ? (string) $form[$key] : 'GEL';
        $chosen = fn (array $choices, mixed $selected): array => array_values(array_intersect_key($choices, array_flip(array_map('strval', (array) $selected))));
        $money = fn (mixed $amount, string $key): ?string => is_numeric($amount) ? $price->format((float) $amount, $currency($key)) : null;

        /** @var list<array{0: string, 1: mixed}> $pairs */
        $pairs = [
            [__('account.wizard.fields.location_id'), $options['locations'][(string) ($form['location_id'] ?? '')] ?? null],
            [__('account.wizard.fields.address'), $form['address'] ?? null],
        ];

        $pairs = [...$pairs, ...match ($this->type) {
            'hotel' => [
                [__('account.wizard.fields.hotel_type'), $options['hotelTypes'][$form['hotel_type']] ?? null],
                [__('account.wizard.fields.stars'), filled($form['stars']) ? $form['stars'].' ★' : null],
                [__('account.wizard.fields.rooms_count'), $form['rooms_count']],
                [__('account.wizard.fields.amenities'), $chosen($options['amenities'], $form['amenities'] ?? [])],
                [__('account.wizard.room.base_price'), ($form['has_rooms'] ?? false) ? null : $money($form['room']['base_price'] ?? null, 'GEL')],
            ],
            'property' => [
                [__('account.wizard.fields.deal'), $options['deals'][$form['deal']] ?? null],
                [__('account.wizard.fields.property_type'), $options['propertyTypes'][$form['property_type']] ?? null],
                [__('account.wizard.fields.price'), $money($form['price'], 'currency')],
                [__('account.wizard.fields.area_m2'), filled($form['area_m2']) ? $form['area_m2'].' m²' : null],
                [__('account.wizard.fields.land_area_m2'), filled($form['land_area_m2']) ? $form['land_area_m2'].' m²' : null],
                [__('account.wizard.fields.rooms'), $form['rooms']],
                [__('account.wizard.fields.floor'), filled($form['floor']) ? $form['floor'].(filled($form['total_floors']) ? ' / '.$form['total_floors'] : '') : null],
                [__('account.wizard.fields.condition'), $options['conditions'][$form['condition']] ?? null],
                [__('account.wizard.fields.features'), $chosen($options['features'], $form['features'] ?? [])],
            ],
            'classified' => [
                [__('account.wizard.fields.category_id'), Category::query()->find(is_numeric($form['category_id']) ? (int) $form['category_id'] : null)?->name],
                [__('account.wizard.fields.price'), $money($form['price'], 'currency') ?? __('site.price.on_request')],
                [__('account.wizard.fields.condition'), $options['conditions'][$form['condition']] ?? null],
                ...array_map(fn (array $field): array => [$field['label'], $this->attributeText($field, $form['attribute_values'][$field['key']] ?? null)], $this->schemaFields()),
            ],
            'vacancy' => [
                [__('account.wizard.fields.category_id'), $options['categories'][(int) $form['category_id']]['name'] ?? null],
                [__('account.wizard.fields.employment_type'), $options['employmentTypes'][$form['employment_type']] ?? null],
                [__('account.wizard.fields.salary'), is_numeric($form['salary_min']) || is_numeric($form['salary_max'])
                    ? collect([$money($form['salary_min'], 'salary_currency'), $money($form['salary_max'], 'salary_currency')])->filter()->implode(' – ').' / '.($options['periods'][$form['salary_period']] ?? '')
                    : null],
                [__('account.wizard.fields.experience'), $options['experience'][$form['experience']] ?? null],
                [__('account.wizard.fields.languages'), $chosen($options['languages'], $form['languages'] ?? [])],
                [__('account.wizard.fields.deadline'), $form['deadline'] ?? null],
            ],
            default => [],
        }];

        $pairs[] = [__('account.wizard.fields.contact_phone'), $this->type === 'vacancy' ? ($form['apply_phone'] ?? null) : ($form['contact_phone'] ?? null)];

        $facts = [];

        foreach ($pairs as [$label, $value]) {
            if ($value !== null && $value !== '' && $value !== []) {
                $facts[] = ['label' => $label, 'value' => is_array($value) ? implode(', ', $value) : (string) $value];
            }
        }

        return $facts;
    }

    /**
     * Display text of a category-schema value in the preview.
     *
     * @param  array{key: string, type: string, label: string, choices: array<string, string>, required: bool, unit: ?string}  $field
     * @return string|list<string>|null
     */
    private function attributeText(array $field, mixed $value): string|array|null
    {
        return match (true) {
            $value === null || $value === '' || $value === [] => null,
            is_array($value) => array_values(array_intersect_key($field['choices'], array_flip(array_map('strval', $value)))),
            is_bool($value) => $value ? __('account.wizard.yes') : null,
            default => ($field['choices'][(string) $value] ?? (string) $value).($field['unit'] ? ' '.$field['unit'] : ''),
        };
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
