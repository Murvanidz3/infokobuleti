@props([
    'markers' => [],
    'searchArea' => true,
    'height' => 'h-[min(72vh,720px)] min-h-[420px]',
])

{{-- Leaflet map with clustered price pins (resources/js/ui/map.js). Livewire pages refresh the pins
     through the "markers-updated" event; "search in this area" calls searchArea() on the component. --}}
<div wire:ignore
     x-data="listingMap(@js($markers), @js(['searchArea' => $searchArea, 'labels' => ['searchArea' => __('site.map.search_area'), 'open' => __('site.map.open')]]))"
     x-on:markers-updated.window="update($event.detail.markers)"
     {{ $attributes->class(['relative isolate overflow-hidden rounded-card border border-line bg-surface-alt', $height]) }}>
    <div x-ref="map" class="absolute inset-0" role="region" aria-label="{{ __('site.map.label') }}"></div>
    <div x-show="loading" class="absolute inset-0 grid place-items-center text-secondary">
        <x-icon name="map" :size="36" :stroke="1.25" />
    </div>
    <button type="button" x-show="moved" x-on:click="searchHere()" style="display: none"
            class="absolute top-4 left-1/2 z-[500] inline-flex min-h-10 -translate-x-1/2 items-center gap-2 rounded-full bg-navy px-4 text-[14px] font-semibold text-white shadow-overlay hover:bg-navy-hover">
        <x-icon name="search" :size="16" />
        {{ __('site.map.search_area') }}
    </button>
</div>
