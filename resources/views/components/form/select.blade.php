@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'icon' => null,
    'invalid' => null,
])

@php
    $invalid ??= $errors->has($name);
    $selected = old($name, $selected);
@endphp

<div class="relative">
    @if ($icon)
        <x-icon :name="$icon" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-secondary" />
    @endif
    <select name="{{ $name }}"
            id="{{ $attributes->get('id', $name) }}"
            @if ($invalid) aria-invalid="true" aria-describedby="{{ $attributes->get('id', $name) }}-error" @endif
            {{ $attributes->except('id')->class([
                'block min-h-12 w-full cursor-pointer appearance-none rounded-field border bg-white pr-11 text-[15px] text-navy transition-colors duration-200 hover:border-line-strong focus:border-navy',
                'pl-11' => $icon,
                'pl-4' => ! $icon,
                'border-coral-pressed' => $invalid,
                'border-line' => ! $invalid,
            ]) }}>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder ?? __('components.select.placeholder') }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $value === (string) $selected)>{{ $text }}</option>
        @endforeach
    </select>
    <x-icon name="chevron-down" :size="18" class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-secondary" />
</div>
