@props([
    'name',
    'options' => [],
    'selected' => null,
    'label' => null,
])

@php
    $selected = old($name, $selected);
@endphp

<div role="radiogroup" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @foreach ($options as $value => $text)
        <label class="cursor-pointer">
            <input type="radio" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked((string) $value === (string) $selected)>
            <span class="inline-flex min-h-11 items-center rounded-full border border-line bg-white px-[18px] text-sm font-semibold text-secondary transition-colors duration-200 hover:border-line-strong hover:text-navy peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ $text }}</span>
        </label>
    @endforeach
</div>
