<div>
    <x-listing.page :total="$total" :chips="$chips" :editing-search="$editingSearch > 0" :sort-options="$sortOptions" :views="$views" :view="$display" :paginator="$paginator"
                    :has-more="$hasMore" :markers="$markers" :active-count="$activeCount" :targets="$targets" :search-placeholder="__('site.real_estate.search_placeholder')">
        <x-slot:filters>
            <x-filters.group :title="__('home.search.fields.deal')">
                <x-filters.pills model="deal" :multiple="false" :options="['' => __('site.filters.any')] + $dealOptions" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.type')">
                <x-filters.pills model="type" :options="$typeOptions" />
            </x-filters.group>

            <x-filters.group :title="__('home.search.fields.price')">
                <div class="mb-2.5 inline-flex rounded-full border border-line p-0.5" role="radiogroup" aria-label="{{ __('site.filters.currency') }}">
                    @foreach (['GEL' => '₾ GEL', 'USD' => '$ USD'] as $code => $label)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="currency" value="{{ $code }}" class="peer sr-only">
                            <span class="block rounded-full px-3 py-1 text-[12.5px] font-semibold text-secondary peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-coral">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-filters.range from="price_min" to="price_max" :prefix="$currency === 'USD' ? '$' : '₾'" step="100" :label="__('home.search.fields.price')" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.area')">
                <x-filters.range from="area_min" to="area_max" suffix="m²" :label="__('site.filters.area')" />
            </x-filters.group>

            <x-filters.group :title="__('home.search.fields.rooms')">
                <x-filters.pills model="rooms" :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4+']" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.floor')">
                <x-filters.range from="floor_min" to="floor_max" :label="__('site.filters.floor')" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.condition')">
                <x-filters.pills model="condition" :options="$conditionOptions" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.building')">
                <x-filters.pills model="building" :multiple="false" :options="['' => __('site.filters.any')] + $buildingOptions" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.sea')">
                <x-filters.pills model="sea" :multiple="false" :options="['' => __('site.filters.any')] + collect(\App\Livewire\Listings\PropertyIndex::SEA_OPTIONS)->mapWithKeys(fn (int $meters): array => [(string) $meters => __('site.filters.sea_up_to', ['distance' => $meters])])->all()" />
            </x-filters.group>

            @if ($amenityOptions)
                <x-filters.group :title="__('site.filters.amenities')">
                    <x-filters.pills model="amenities" :options="$amenityOptions" />
                </x-filters.group>
            @endif

            @if ($locationOptions)
                <x-filters.group :title="__('home.search.fields.location')">
                    <x-form.select :aria-label="__('home.search.fields.location')" name="location" wire:model.live="location" :options="$locationOptions" :placeholder="__('site.filters.all_locations')" />
                </x-filters.group>
            @endif

            <x-filters.group :title="__('site.filters.more')">
                <label class="inline-flex min-h-10 cursor-pointer items-center gap-3 text-[15px] text-navy">
                    <input type="checkbox" wire:model.live="photos" class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">
                    {{ __('site.filters.photos_only') }}
                </label>
            </x-filters.group>
        </x-slot:filters>

        @if ($display === 'list')
            <div class="flex flex-col gap-5">
                @foreach ($items as $property)
                    <x-card.property wire:key="property-{{ $property['favorite'] }}" horizontal :badge="$property['badge']" :price="$property['price']" :converted="$property['converted']" :title="$property['title']" :location="$property['location']" :features="$property['features']" :url="$property['url']" :image="$property['image']" :saved="$property['saved']" :favorite="$property['favorite']" :eager="$loop->first" :promotion="$property['promotion'] ?? null" :logo="$property['logo'] ?? null" :owner-verified="$property['ownerVerified'] ?? false" />
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,290px),1fr))] gap-5">
                @foreach ($items as $property)
                    <x-card.property wire:key="property-{{ $property['favorite'] }}" :badge="$property['badge']" :price="$property['price']" :converted="$property['converted']" :title="$property['title']" :location="$property['location']" :features="$property['features']" :url="$property['url']" :image="$property['image']" :saved="$property['saved']" :favorite="$property['favorite']" :eager="$loop->first" :promotion="$property['promotion'] ?? null" :logo="$property['logo'] ?? null" :owner-verified="$property['ownerVerified'] ?? false" />
                @endforeach
            </div>
        @endif
    </x-listing.page>
</div>
