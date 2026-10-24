@props([
    'from',
    'to',
    'fromValue' => null,
    'toValue' => null,
    'label' => null,
])

@php
    $control = 'block min-h-12 w-full min-w-0 bg-transparent pr-3 pl-11 text-[15px] text-navy';
@endphp

<div role="group" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['grid grid-cols-2 divide-x divide-line rounded-field border border-line bg-white transition-colors duration-200 hover:border-line-strong focus-within:border-navy']) }}>
    <div class="relative">
        <x-icon name="calendar" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-secondary" />
        <input type="date" name="{{ $from }}" id="{{ $from }}" value="{{ old($from, $fromValue) }}" aria-label="{{ __('components.range.from') }}" class="{{ $control }} rounded-l-field">
    </div>
    <div class="relative">
        <x-icon name="calendar" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-secondary" />
        <input type="date" name="{{ $to }}" id="{{ $to }}" value="{{ old($to, $toValue) }}" aria-label="{{ __('components.range.to') }}" class="{{ $control }} rounded-r-field">
    </div>
</div>
