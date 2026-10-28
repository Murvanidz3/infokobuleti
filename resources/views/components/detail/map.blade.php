@props([
    'lat' => null,
    'lng' => null,
    'title' => '',
    'nearby' => [],
    'address' => null,
])

@if ($lat !== null && $lng !== null)
    <div {{ $attributes }}>
        <div wire:ignore x-data="placeMap(@js(['lat' => (float) $lat, 'lng' => (float) $lng, 'title' => $title]), @js($nearby), @js(__('site.map.open')))"
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
        @if ($nearby)
            <ul class="mt-5 grid grid-cols-1 gap-2 xs:grid-cols-2">
                @foreach ($nearby as $place)
                    <li class="min-w-0">
                        <a href="{{ $place['url'] }}" class="flex items-center gap-3 rounded-tile border border-line p-2.5 hover:border-line-strong">
                            <span class="size-12 shrink-0 overflow-hidden rounded-chip bg-line"><x-image :src="$place['image']" alt="" /></span>
                            <span class="min-w-0">
                                <span class="block truncate text-[14.5px] font-semibold text-navy">{{ $place['title'] }}</span>
                                <span class="block text-[12.5px] text-secondary">{{ $place['meta'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
