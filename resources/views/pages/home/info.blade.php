<x-section :background="$alt ? 'alt' : 'white'" spacing="tight" class="border-y border-line" aria-labelledby="info-title">
    <div class="mb-5 flex flex-wrap items-baseline justify-between gap-4">
        <h2 id="info-title" class="font-serif text-[22px] font-semibold text-navy">{{ __('home.info.title') }}</h2>
        @if ($info['updated'])
            <span class="text-[13.5px] text-secondary">{{ $info['updated'] }}</span>
        @endif
    </div>
    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,250px),1fr))] gap-4">
        <x-info-widget icon="sun" :label="__('home.info.weather')">
            <div class="flex items-end gap-5">
                <x-info-widget.stat size="xl" :value="$info['weather']['air'] ?? '—'" :caption="$info['weather']['condition'] ?? __('site.weather.unavailable')" />
                <x-info-widget.stat size="xl" tone="sea" class="border-l border-line pl-5" :value="$info['weather']['sea'] ?? '—'" :caption="__('home.info.sea_water')" />
            </div>
        </x-info-widget>
        <x-info-widget icon="exchange" :label="__('home.info.rates')">
            <dl class="grid grid-cols-3 gap-2">
                @foreach ($info['rates'] as $currency => $rate)
                    <div>
                        <dt class="text-xs font-semibold text-secondary">{{ $currency }}</dt>
                        <dd class="mt-1 text-[19px] font-semibold text-navy">{{ $rate }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-info-widget>
        <x-info-widget icon="plane" :label="__('home.info.airport')">
            <x-info-widget.stat :value="$info['airport']['distance']" :caption="$info['airport']['note']" />
        </x-info-widget>
        <x-info-widget icon="train" :label="__('home.info.railway')">
            <x-info-widget.stat :value="$info['railway']['value']" :caption="$info['railway']['note']" />
        </x-info-widget>
    </div>
</x-section>
