@props([
    'from',
    'to',
    'prefix' => null,
    'suffix' => null,
    'label' => null,
    'step' => 1,
])

@php
    $control = \Illuminate\Support\Arr::toCssClasses([
        'block min-h-11 w-full min-w-0 bg-transparent text-[15px] text-navy placeholder:text-secondary/70',
        'pl-8' => $prefix,
        'pl-3.5' => ! $prefix,
        'pr-9' => $suffix,
        'pr-3' => ! $suffix,
    ]);
@endphp

<div role="group" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['grid grid-cols-2 divide-x divide-line rounded-field border border-line bg-white transition-colors duration-200 hover:border-line-strong focus-within:border-navy']) }}>
    @foreach ([[$from, __('components.range.from'), 'rounded-l-field'], [$to, __('components.range.to'), 'rounded-r-field']] as [$property, $placeholder, $rounding])
        <div class="relative">
            @if ($prefix)
                <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-[14px] text-secondary">{{ $prefix }}</span>
            @endif
            <input type="number" inputmode="numeric" min="0" step="{{ $step }}" wire:model.live.debounce.600ms="{{ $property }}"
                   placeholder="{{ $placeholder }}" aria-label="{{ ($label ? $label.': ' : '').$placeholder }}" class="{{ $control }} {{ $rounding }}">
            @if ($suffix)
                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-[13px] text-secondary">{{ $suffix }}</span>
            @endif
        </div>
    @endforeach
</div>
