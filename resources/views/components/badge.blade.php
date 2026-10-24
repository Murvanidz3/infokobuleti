@props(['variant' => 'tint'])

{{-- navy: sale/rent on photos · new: coral "new" · tint: news tags · outline: seasonal job type · glass: tags on photos --}}
<span {{ $attributes->class([
    'inline-flex shrink-0 items-center rounded-full font-semibold whitespace-nowrap',
    'bg-navy px-3 py-1.5 text-[12.5px] text-white' => $variant === 'navy',
    'bg-coral-pressed px-2 py-0.5 text-[11px] text-white' => $variant === 'new',
    'bg-line px-2.5 py-[3px] text-xs text-navy' => $variant === 'tint',
    'border border-line-strong bg-white px-2.5 py-0.5 text-xs text-navy' => $variant === 'outline',
    'border border-line-strong bg-line px-2.5 py-0.5 text-xs text-navy' => $variant === 'outline-tint',
    'border border-white/30 bg-white/16 px-2.5 py-1 text-xs text-white backdrop-blur-[8px]' => $variant === 'glass',
]) }}>{{ $slot }}</span>
