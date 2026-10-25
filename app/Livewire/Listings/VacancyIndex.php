<?php

namespace App\Livewire\Listings;

use App\Enums\CategoryModule;
use App\Enums\EmploymentType;
use App\Enums\ListingModule;
use App\Models\Category;
use App\Models\Vacancy;
use App\Services\PriceFormatter;
use App\Support\Listings\CardPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * /jobs: category, employment type (full-time, seasonal…), minimum salary, posting date, location.
 *
 * @extends ListingIndex<Vacancy>
 */
class VacancyIndex extends ListingIndex
{
    public const array POSTED_OPTIONS = [1, 3, 7, 30];

    /** Jobs read best as a list; there is no grid. */
    #[Url(as: 'view', except: 'list')]
    public string $display = 'list';

    #[Url(except: '')]
    public string $category = '';

    /** @var list<string> */
    #[Url(except: [])]
    public $employment = [];

    #[Url(except: '')]
    public string $salary = '';

    #[Url(except: '')]
    public string $posted = '';

    #[Url(except: '')]
    public string $location = '';

    protected function model(): string
    {
        return Vacancy::class;
    }

    protected function module(): ListingModule
    {
        return ListingModule::Vacancies;
    }

    protected function relations(): array
    {
        return ['location', 'businessProfile'];
    }

    protected function viewName(): string
    {
        return 'livewire.listings.jobs';
    }

    protected function views(): array
    {
        return ['list', 'map'];
    }

    protected function filterProperties(): array
    {
        return ['category', 'employment', 'salary', 'posted', 'location'];
    }

    protected function sortOptions(): array
    {
        return [
            'recommended' => __('site.sort.recommended'),
            'newest' => __('site.sort.newest'),
            'salary_desc' => __('site.sort.salary_desc'),
        ];
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('published_at'),
            'salary_desc' => $query->orderByRaw('coalesce(salary_max, salary_min) desc nulls last'),
            default => $query->orderByPromotion(),
        };
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->category !== '') {
            $category = Category::query()->forModule(CategoryModule::Vacancies)->where('slug', $this->category)->first();
            $query->whereIn('category_id', $category === null ? [0] : [$category->id, ...$category->children()->pluck('id')->all()]);
        }

        $types = array_values(array_intersect($this->strings($this->employment), EmploymentType::values()));

        if ($types !== []) {
            $query->whereIn('employment_type', $types);
        }

        if (($salary = $this->number($this->salary)) !== null && $salary > 0) {
            // Offers whose range reaches the wanted salary (GEL vacancies; others are rare).
            $query->whereRaw('coalesce(salary_max, salary_min) >= ?', [$salary]);
        }

        if (in_array((int) $this->posted, self::POSTED_OPTIONS, true)) {
            $query->where('published_at', '>=', now()->subDays((int) $this->posted));
        }

        if ($this->location !== '') {
            $query->whereIn('location_id', $this->locationIds($this->location));
        }
    }

    protected function card(Model $model): array
    {
        return app(CardPresenter::class)->job($model);
    }

    protected function filterOptions(): array
    {
        return [
            'categoryOptions' => Category::query()->forModule(CategoryModule::Vacancies)->whereNull('parent_id')->where('is_active', true)->orderBy('sort')->get()
                ->mapWithKeys(fn (Category $category): array => [$category->slug => (string) $category->name])->all(),
            'employmentOptions' => EmploymentType::options(),
            'locationOptions' => $this->locationOptions(),
        ];
    }

    protected function chips(): array
    {
        $chips = [];

        if ($this->category !== '') {
            $chips[] = ['label' => $this->filterOptions()['categoryOptions'][$this->category] ?? $this->category, 'property' => 'category'];
        }

        foreach ($this->strings($this->employment) as $type) {
            $chips[] = ['label' => EmploymentType::tryFrom($type)?->getLabel() ?? $type, 'property' => 'employment', 'value' => $type];
        }

        if ($this->salary !== '') {
            $chips[] = ['label' => __('site.filters.from', ['value' => app(PriceFormatter::class)->format((float) $this->salary)]), 'property' => 'salary'];
        }

        if ($this->posted !== '') {
            $chips[] = ['label' => trans_choice('site.filters.posted_days', (int) $this->posted, ['count' => (int) $this->posted]), 'property' => 'posted'];
        }

        if ($this->location !== '') {
            $chips[] = ['label' => $this->locationLabel($this->location), 'property' => 'location'];
        }

        return $chips;
    }
}
