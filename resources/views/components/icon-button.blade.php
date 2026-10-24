@props([
    'icon',
    'label',
    'variant' => 'outline',
    'href' => null,
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'grid size-11 shrink-0 place-items-center rounded-full transition-colors duration-200',
        'border border-line-strong bg-white text-navy hover:border-coral hover:bg-coral hover:text-white' => $variant === 'outline',
        'bg-transparent text-navy hover:bg-surface-alt' => $variant === 'ghost',
        'border border-white/18 text-white hover:border-coral hover:bg-coral hover:text-white' => $variant === 'inverse',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" {{ $attributes->class($classes) }}><x-icon :name="$icon" :size="18" /></a>
@else
    <button type="button" aria-label="{{ $label }}" {{ $attributes->class($classes) }}><x-icon :name="$icon" :size="18" /></button>
@endif
