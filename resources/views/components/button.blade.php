@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center gap-2.5 rounded-button font-semibold whitespace-nowrap transition-[translate,background-color,border-color,color,box-shadow] duration-200 disabled:pointer-events-none disabled:opacity-50',
        'bg-coral-pressed text-white hover:bg-coral hover:text-white hover:shadow-button-hover active:bg-coral-pressed motion-safe:hover:-translate-y-0.5' => $variant === 'primary',
        'border border-coral bg-white text-navy hover:bg-coral-tint' => $variant === 'secondary',
        'bg-navy text-white hover:bg-navy-hover hover:text-white' => $variant === 'tertiary',
        'bg-transparent text-navy hover:bg-surface-alt' => $variant === 'ghost',
        'hit-44 relative min-h-9 px-3.5 text-[13.5px]' => $size === 'sm',
        'min-h-11 px-5 text-[14.5px]' => $size === 'md',
        'min-h-14 px-7 text-base' => $size === 'lg',
    ]);
    $iconSize = $size === 'sm' ? 16 : 18;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
        @if ($iconRight)<x-icon :name="$iconRight" :size="$iconSize" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
        @if ($iconRight)<x-icon :name="$iconRight" :size="$iconSize" />@endif
    </button>
@endif
