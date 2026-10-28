<?php

namespace App\Livewire\Listings;

use App\Enums\CategoryModule;
use App\Enums\ItemCondition;
use App\Enums\ListingModule;
use App\Models\Category;
use App\Models\Classified;
use App\Services\PriceFormatter;
use App\Support\AttributeSchema;
use App\Support\Listings\CardPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * /classifieds: category (with its sub-categories) plus the filterable attributes of that
 * category's schema, price, condition, location and "with photos only".
 *
 * @extends ListingIndex<Classified>
 */
class ClassifiedIndex extends ListingIndex
{
    #[Url(except: '')]
    public string $category = '';

    /** @var array<string, mixed> attribute key => value(s); numbers use "{key}_min" / "{key}_max" */
    #[Url(except: [])]
    public $attrs = [];

    #[Url(except: '')]
    public string $price_min = '';

    #[Url(except: '')]
    public string $price_max = '';

    #[Url(except: '')]
    public string $condition = '';

    #[Url(except: '')]
    public string $location = '';

    #[Url(except: false)]
    public bool $photos = false;

    private ?Category $selected = null;

    private bool $resolved = false;

    protected function model(): string
    {
        return Classified::class;
    }

    protected function module(): ListingModule
    {
        return ListingModule::Classifieds;
    }

    protected function relations(): array
    {
        return ['media', 'location', 'category.parent'];
    }

    protected function viewName(): string
    {
        return 'livewire.listings.classifieds';
    }

    protected function filterProperties(): array
    {
        return ['category', 'attrs', 'price_min', 'price_max', 'condition', 'location', 'photos'];
    }

    public function updatedCategory(): void
    {
        // Attributes belong to a category: drop them when it changes.
        $this->attrs = [];
        $this->resolved = false;
    }

    protected function sortOptions(): array
    {
        return [
            'recommended' => __('site.sort.recommended'),
            'newest' => __('site.sort.newest'),
            'price_asc' => __('site.sort.price_asc'),
            'price_desc' => __('site.sort.price_desc'),
        ];
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('published_at'),
            'price_asc' => $query->orderByRaw('price_gel asc nulls last'),
            'price_desc' => $query->orderByRaw('price_gel desc nulls last'),
            default => $query->orderByPromotion(),
        };
    }

    public function selectedCategory(): ?Category
    {
        if (! $this->resolved) {
            $this->resolved = true;
            $this->selected = $this->category === '' ? null : Category::query()
                ->forModule(CategoryModule::Classifieds)
                ->where('slug', $this->category)
                ->with(['parent', 'children' => fn ($query) => $query->where('is_active', true)->orderBy('sort')])
                ->first();
        }

        return $this->selected;
    }

    /**
     * Filterable fields of the selected category's schema.
     *
     * @return list<array<string, mixed>>
     */
    public function attributeFields(): array
    {
        $category = $this->selectedCategory();

        if ($category === null) {
            return [];
        }

        return array_values(array_filter(
            $category->schema()->fields(),
            fn (array $field): bool => ($field['filterable'] ?? true) && in_array($field['type'], ['select', 'multiselect', 'boolean', 'number'], true),
        ));
    }

    protected function applyFilters(Builder $query): void
    {
        $category = $this->selectedCategory();

        if ($this->category !== '') {
            $query->whereIn('category_id', $category === null ? [0] : [$category->id, ...$category->children->modelKeys()]);
        }

        foreach ($this->attributeFields() as $field) {
            $key = $field['key'];
            $column = "attribute_values->{$key}";

            match ($field['type']) {
                'select' => isset($this->attrs[$key]) && is_string($this->attrs[$key]) && $this->attrs[$key] !== ''
                    ? $query->where($column, $this->attrs[$key])
                    : null,
                'multiselect' => ! empty($this->attrs[$key])
                    ? $query->where(fn (Builder $query) => collect((array) $this->attrs[$key])->each(fn ($value) => $query->orWhereJsonContains($column, (string) $value)))
                    : null,
                'boolean' => ! empty($this->attrs[$key])
                    ? $query->whereRaw('(attribute_values->>?)::boolean = true', [$key])
                    : null,
                default => null,
            };

            if ($field['type'] === 'number') {
                foreach (['min' => '>=', 'max' => '<='] as $suffix => $operator) {
                    $value = $this->attrs["{$key}_{$suffix}"] ?? null;

                    if (is_numeric($value)) {
                        $query->whereRaw("(case when jsonb_typeof(attribute_values->?) = 'number' then (attribute_values->>?)::numeric end) {$operator} ?", [$key, $key, (float) $value]);
                    }
                }
            }
        }

        if (($min = $this->number($this->price_min)) !== null) {
            $query->where('price_gel', '>=', $min);
        }

        if (($max = $this->number($this->price_max)) !== null && $max > 0) {
            $query->where('price_gel', '<=', $max);
        }

        if (ItemCondition::tryFrom($this->condition) !== null) {
            $query->where('condition', $this->condition);
        }

        if ($this->location !== '') {
            $query->whereIn('location_id', $this->locationIds($this->location));
        }

        if ($this->photos) {
            $this->filterPhotosOnly($query);
        }
    }

    protected function card(Model $model): array
    {
        return app(CardPresenter::class)->classified($model);
    }

    protected function filterOptions(): array
    {
        $selected = $this->selectedCategory();
        $root = $selected === null ? null : ($selected->parent ?? $selected);

        return [
            'roots' => Category::query()->forModule(CategoryModule::Classifieds)->whereNull('parent_id')->where('is_active', true)->orderBy('sort')->get()
                ->mapWithKeys(fn (Category $category): array => [$category->slug => (string) $category->name])->all(),
            'rootSlug' => $root === null ? '' : $root->slug,
            'children' => $root === null ? [] : Category::query()->where('parent_id', $root->id)->where('is_active', true)->orderBy('sort')->get()
                ->mapWithKeys(fn (Category $category): array => [$category->slug => (string) $category->name])->all(),
            'fields' => array_map(fn (array $field): array => [
                ...$field,
                'name' => $field['label'][app()->getLocale()] ?? $field['label']['ka'] ?? $field['key'],
                'choices' => AttributeSchema::choices($field),
            ], $this->attributeFields()),
            'conditionOptions' => ItemCondition::options(),
            'locationOptions' => $this->locationOptions(),
        ];
    }

    protected function chips(): array
    {
        $chips = [];

        if (($category = $this->selectedCategory()) !== null) {
            $chips[] = ['label' => (string) $category->name, 'property' => 'category'];
        }

        foreach ($this->filterOptions()['fields'] as $field) {
            $key = $field['key'];

            if ($field['type'] === 'number') {
                $min = $this->attrs["{$key}_min"] ?? '';
                $max = $this->attrs["{$key}_max"] ?? '';

                if ($min !== '' || $max !== '') {
                    $chips[] = ['label' => $field['name'].': '.($min !== '' ? $min : '…').'–'.($max !== '' ? $max : '…'), 'property' => 'attrs', 'value' => "{$key}_min"];
                }

                continue;
            }

            foreach ((array) ($this->attrs[$key] ?? []) as $value) {
                if (! is_scalar($value) || $value === '' || $value === false) {
                    continue;
                }

                $label = $field['type'] === 'boolean' ? $field['name'] : $field['name'].': '.($field['choices'][$value] ?? $value);
                $chips[] = ['label' => $label, 'property' => 'attrs', 'value' => $key];
            }
        }

        if ($this->price_min !== '' || $this->price_max !== '') {
            $chips[] = ['label' => $this->rangeLabel(app(PriceFormatter::class), $this->price_min, $this->price_max), 'property' => 'price_min'];
        }

        if (($condition = ItemCondition::tryFrom($this->condition)) !== null) {
            $chips[] = ['label' => $condition->getLabel(), 'property' => 'condition'];
        }

        if ($this->location !== '') {
            $chips[] = ['label' => $this->locationLabel($this->location), 'property' => 'location'];
        }

        if ($this->photos) {
            $chips[] = ['label' => __('site.filters.photos_only'), 'property' => 'photos'];
        }

        return $chips;
    }

    public function removeFilter(string $property, ?string $value = null): void
    {
        if ($property === 'attrs' && $value !== null) {
            unset($this->attrs[$value]);

            if (str_ends_with($value, '_min')) {
                unset($this->attrs[substr($value, 0, -4).'_max']);
            }

            $this->updated('attrs');

            return;
        }

        if ($property === 'price_min') {
            $this->reset('price_max');
        }

        if ($property === 'category') {
            $this->attrs = [];
            $this->resolved = false;
        }

        parent::removeFilter($property, $value);
    }

    public function resetFilters(): void
    {
        $this->resolved = false;
        parent::resetFilters();
    }
}
