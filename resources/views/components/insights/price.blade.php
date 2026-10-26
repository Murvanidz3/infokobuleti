{{-- Price per m² against comparable listings (PriceInsight). $owner switches the wording for the add-listing form. --}}
@props(['insight', 'owner' => false])

@if ($insight)
    <div {{ $attributes->class(['rounded-card border border-line bg-white p-5']) }}>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="flex items-center gap-2 text-[15px] font-semibold text-navy"><x-icon name="chart" :size="18" />{{ __('insights.price.title') }}</h3>
            <span @class([
                'inline-flex items-center rounded-full px-2.5 py-[3px] text-xs font-semibold',
                'bg-navy text-white' => $insight['verdict'] === 'below',
                'bg-line text-navy' => $insight['verdict'] === 'fair',
                'border border-coral bg-white text-navy' => $insight['verdict'] === 'above',
            ])>{{ __('insights.price.verdict.'.$insight['verdict'], ['percent' => $insight['diff']]) }}</span>
        </div>

        <p class="mt-2 text-[14px] leading-normal text-secondary">
            {{ __($owner ? 'insights.price.owner_text' : 'insights.price.text', ['value' => $insight['value'], 'median' => $insight['median'], 'count' => $insight['count'], 'area' => $insight['area']]) }}
        </p>

        <div class="relative mt-5 mb-1 h-2 rounded-full bg-surface-alt" role="img" aria-label="{{ __('insights.price.range', ['from' => $insight['range'][0], 'to' => $insight['range'][1]]) }}">
            <span class="absolute inset-y-0 rounded-full bg-line-strong" style="left: {{ $insight['bar']['p25'] }}%; right: {{ 100 - $insight['bar']['p75'] }}%"></span>
            <span class="absolute -top-1 h-4 w-0.5 -translate-x-1/2 bg-navy" style="left: {{ $insight['bar']['median'] }}%" title="{{ __('insights.price.median') }}"></span>
            <span class="absolute top-1/2 size-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-coral-pressed shadow-card" style="left: {{ $insight['bar']['value'] }}%" title="{{ $insight['value'] }}"></span>
        </div>
        <div class="mt-2 flex justify-between text-[12px] text-secondary">
            <span>{{ __('insights.price.range', ['from' => $insight['range'][0], 'to' => $insight['range'][1]]) }}</span>
            <span class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full bg-coral-pressed"></span>{{ $owner ? __('insights.price.yours') : __('insights.price.this') }}</span>
        </div>
    </div>
@endif
