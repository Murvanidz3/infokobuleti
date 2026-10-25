@php
    $control = 'block min-h-11 w-full min-w-0 rounded-field border border-line bg-white px-3 text-[15px] text-navy hover:border-line-strong focus:border-navy';
@endphp

<div>
    <x-listing.page :total="$total" :chips="$chips" :sort-options="$sortOptions" :views="$views" :view="$display" :paginator="$paginator"
                    :has-more="$hasMore" :markers="$markers" :active-count="$activeCount" :targets="$targets" :search-placeholder="__('site.hotels.search_placeholder')">
        <x-slot:filters>
            <x-filters.group :title="__('site.filters.dates')">
                <div class="grid grid-cols-2 gap-2">
                    <label>
                        <span class="mb-1 block text-[12.5px] text-secondary">{{ __('home.search.fields.check_in') }}</span>
                        <input type="date" wire:model.live="check_in" min="{{ today()->toDateString() }}" class="{{ $control }}">
                    </label>
                    <label>
                        <span class="mb-1 block text-[12.5px] text-secondary">{{ __('home.search.fields.check_out') }}</span>
                        <input type="date" wire:model.live="check_out" min="{{ $check_in ?: today()->addDay()->toDateString() }}" class="{{ $control }}">
                    </label>
                </div>
            </x-filters.group>

            <x-filters.group :title="__('home.search.fields.guests')">
                <x-filters.pills model="guests" :multiple="false" :options="['' => __('site.filters.any')] + collect(range(1, 6))->mapWithKeys(fn ($n) => [(string) $n => $n === 6 ? '6+' : (string) $n])->all()" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.price_night')">
                <x-filters.range from="price_min" to="price_max" prefix="₾" :label="__('site.filters.price_night')" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.type')">
                <x-filters.pills model="type" :options="$typeOptions" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.stars')">
                <x-filters.pills model="stars" :options="collect(range(1, 5))->mapWithKeys(fn ($n) => [(string) $n => $n.' ★'])->all()" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.sea')">
                <x-filters.pills model="sea" :multiple="false" :options="['' => __('site.filters.any')] + collect(\App\Livewire\Listings\HotelIndex::SEA_OPTIONS)->mapWithKeys(fn ($m) => [(string) $m => __('site.filters.sea_up_to', ['distance' => $m])])->all()" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.rating')">
                <x-filters.pills model="rating" :multiple="false" :options="['' => __('site.filters.any')] + collect(\App\Livewire\Listings\HotelIndex::RATING_OPTIONS)->mapWithKeys(fn ($r) => [(string) $r => $r.'+'])->all()" />
            </x-filters.group>

            @if ($amenityOptions)
                <x-filters.group :title="__('site.filters.amenities')">
                    <x-filters.pills model="amenities" :options="$amenityOptions" />
                </x-filters.group>
            @endif

            @if ($locationOptions)
                <x-filters.group :title="__('home.search.fields.location')">
                    <x-form.select name="location" wire:model.live="location" :options="$locationOptions" :placeholder="__('site.filters.all_locations')" />
                </x-filters.group>
            @endif
        </x-slot:filters>

        @if ($display === 'list')
            <div class="flex flex-col gap-5">
                @foreach ($items as $hotel)
                    <x-card.hotel wire:key="hotel-{{ $hotel['favorite'] }}" horizontal :name="$hotel['name']" :address="$hotel['address']" :sea="$hotel['sea']" :rating="$hotel['rating']" :reviews="$hotel['reviews']" :price="$hotel['price']" :url="$hotel['url']" :image="$hotel['image']" :saved="$hotel['saved']" :favorite="$hotel['favorite']" :eager="$loop->first" />
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-5">
                @foreach ($items as $hotel)
                    <x-card.hotel wire:key="hotel-{{ $hotel['favorite'] }}" :name="$hotel['name']" :address="$hotel['address']" :sea="$hotel['sea']" :rating="$hotel['rating']" :reviews="$hotel['reviews']" :price="$hotel['price']" :url="$hotel['url']" :image="$hotel['image']" :saved="$hotel['saved']" :favorite="$hotel['favorite']" :eager="$loop->first" />
                @endforeach
            </div>
        @endif
    </x-listing.page>
</div>
