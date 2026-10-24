@props([
    'from',
    'to',
    'fromValue' => null,
    'toValue' => null,
    'prefix' => null,
    'suffix' => null,
    'min' => 0,
    'step' => 1,
    'label' => null,
])

@php
    $control = \Illuminate\Support\Arr::toCssClasses([
        'block min-h-12 w-full min-w-0 bg-transparent text-[15px] text-navy placeholder:text-secondary/70',
        'pl-9' => $prefix,
        'pl-4' => ! $prefix,
        'pr-10' => $suffix,
        'pr-4' => ! $suffix,
    ]);
    $fields = [
        [$from, $fromValue, __('components.range.from'), 'rounded-l-field'],
        [$to, $toValue, __('components.range.to'), 'rounded-r-field'],
    ];
@endphp

<div role="group" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['grid grid-cols-2 divide-x divide-line rounded-field border border-line bg-white transition-colors duration-200 hover:border-line-strong focus-within:border-navy']) }}>
    @foreach ($fields as [$name, $value, $placeholder, $rounding])
        <div class="relative">
            @if ($prefix)
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-[15px] text-secondary">{{ $prefix }}</span>
            @endif
            <input type="number" inputmode="numeric" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}" min="{{ $min }}" step="{{ $step }}"
                   placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}" class="{{ $control }} {{ $rounding }}">
            @if ($suffix)
                <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-[13px] text-secondary">{{ $suffix }}</span>
            @endif
        </div>
    @endforeach
</div>
