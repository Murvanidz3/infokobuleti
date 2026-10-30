<div>
    <x-listing.page :total="$total" :chips="$chips" :editing-search="$editingSearch > 0" :sort-options="$sortOptions" :views="$views" :view="$display" :paginator="$paginator"
                    :has-more="$hasMore" :markers="$markers" :active-count="$activeCount" :targets="$targets" :search-placeholder="__('site.projects.search_placeholder')">
        <x-slot:filters>
            <x-filters.group :title="__('site.projects.price_from_label')">
                <div class="mb-2.5 inline-flex rounded-full border border-line p-0.5" role="radiogroup" aria-label="{{ __('site.filters.currency') }}">
                    @foreach (['GEL' => '₾ GEL', 'USD' => '$ USD'] as $code => $label)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="currency" value="{{ $code }}" class="peer sr-only">
                            <span class="block rounded-full px-3 py-1 text-[12.5px] font-semibold text-secondary peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-coral">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-filters.range from="price_min" to="price_max" :prefix="$currency === 'USD' ? '$' : '₾'" suffix="m²" step="50" :label="__('site.projects.price_from_label')" />
            </x-filters.group>

            @if ($locationOptions)
                <x-filters.group :title="__('home.search.fields.location')">
                    <x-form.select :aria-label="__('home.search.fields.location')" name="location" wire:model.live="location" :options="$locationOptions" :placeholder="__('site.filters.all_locations')" />
                </x-filters.group>
            @endif
        </x-slot:filters>

        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,290px),1fr))] gap-5">
            @foreach ($items as $project)
                <x-card.property wire:key="project-{{ $project['favorite'] }}" :badge="$project['badge']" :price="$project['price']" :converted="$project['converted']" :title="$project['title']" :location="$project['location']" :features="$project['features']" :url="$project['url']" :image="$project['image']" :saved="$project['saved']" :favorite="$project['favorite']" :eager="$loop->first" :promotion="$project['promotion'] ?? null" :logo="$project['logo'] ?? null" />
            @endforeach
        </div>
    </x-listing.page>
</div>
