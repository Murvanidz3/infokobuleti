{{-- VIP / Super VIP mark on listing cards. Super VIP: navy; VIP: coral fill (white text, AA). --}}
@props(['promotion' => null])

@if ($promotion)
    <span {{ $attributes->class([
        'inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11.5px] leading-none font-bold whitespace-nowrap text-white',
        'bg-navy' => $promotion === 'super_vip',
        'bg-coral-pressed' => $promotion === 'vip',
    ]) }}>
        <x-icon :name="$promotion === 'super_vip' ? 'crown' : 'star'" :size="12" :stroke="2" />{{ __('enums.promotion_type.'.$promotion) }}
    </span>
@endif
