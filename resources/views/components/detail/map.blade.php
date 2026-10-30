@props([
    'lat' => null,
    'lng' => null,
    'title' => '',
    'address' => null,
])

@if ($lat !== null && $lng !== null)
    <div {{ $attributes }}>
        <div wire:ignore x-data="placeMap(@js(['lat' => (float) $lat, 'lng' => (float) $lng, 'title' => $title]))"
             class="relative isolate h-[340px] overflow-hidden rounded-card border border-line bg-surface-alt">
            <div x-ref="map" class="absolute inset-0" role="region" aria-label="{{ __('site.map.label') }}"></div>
            <div x-show="loading" class="absolute inset-0 grid place-items-center text-secondary"><x-icon name="map" :size="36" :stroke="1.25" /></div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-[14px]">
            @if ($address)
                <span class="flex items-center gap-2 text-secondary"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</span>
            @endif
            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $lat }},{{ $lng }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-coral-pressed hover:underline">
                <x-icon name="navigation" :size="16" />{{ __('site.map.directions') }}
            </a>
        </div>
    </div>
@endif
