<div>
    <x-listing.page :total="$total" :chips="$chips" :editing-search="$editingSearch > 0" :sort-options="$sortOptions" :views="$views" :view="$display" :paginator="$paginator"
                    :has-more="$hasMore" :markers="$markers" :active-count="$activeCount" :targets="$targets" :search-placeholder="__('home.search.placeholders.keyword')">
        <x-slot:filters>
            <x-filters.group :title="__('home.search.fields.category')">
                <x-form.select name="category" wire:model.live="category" :options="$categoryOptions" :placeholder="__('site.filters.all_categories')" />
            </x-filters.group>

            <x-filters.group :title="__('home.search.fields.employment')">
                <x-filters.pills model="employment" :options="$employmentOptions" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.salary_from')">
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-[14px] text-secondary">₾</span>
                    <input type="number" inputmode="numeric" min="0" step="100" wire:model.live.debounce.600ms="salary" aria-label="{{ __('site.filters.salary_from') }}"
                           class="block min-h-11 w-full rounded-field border border-line bg-white pr-3 pl-8 text-[15px] text-navy hover:border-line-strong focus:border-navy">
                </div>
            </x-filters.group>

            <x-filters.group :title="__('site.filters.posted')">
                <x-filters.pills model="posted" :multiple="false" :options="['' => __('site.filters.any')] + collect(\App\Livewire\Listings\VacancyIndex::POSTED_OPTIONS)->mapWithKeys(fn ($days) => [(string) $days => trans_choice('site.filters.posted_days', $days, ['count' => $days])])->all()" />
            </x-filters.group>

            @if ($locationOptions)
                <x-filters.group :title="__('home.search.fields.location')">
                    <x-form.select name="location" wire:model.live="location" :options="$locationOptions" :placeholder="__('site.filters.all_locations')" />
                </x-filters.group>
            @endif
        </x-slot:filters>

        <x-list-panel>
            @foreach ($items as $job)
                <x-card.job wire:key="job-{{ $job['favorite'] }}" :title="$job['title']" :employer="$job['employer']" :salary="$job['salary']" :period="$job['period']" :type="$job['type']" :seasonal="$job['seasonal']" :posted="$job['posted']" :url="$job['url']" />
            @endforeach
        </x-list-panel>
    </x-listing.page>
</div>
