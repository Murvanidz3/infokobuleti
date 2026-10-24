@props(['active' => false, 'href' => null])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex min-h-11 items-center gap-2 rounded-full border px-[18px] text-sm font-semibold whitespace-nowrap transition-colors duration-200',
        'border-navy bg-navy text-white hover:text-white' => $active,
        'border-line bg-white text-secondary hover:border-line-strong hover:text-navy' => ! $active,
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($active) aria-current="true" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" aria-pressed="{{ $active ? 'true' : 'false' }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
