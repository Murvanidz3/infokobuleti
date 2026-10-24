@props([
    'name',
    'type' => 'text',
    'icon' => null,
    'value' => null,
    'invalid' => null,
])

@php
    $invalid ??= $errors->has($name);
@endphp

<div class="relative">
    @if ($icon)
        <x-icon :name="$icon" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-secondary" />
    @endif
    <input type="{{ $type }}"
           name="{{ $name }}"
           id="{{ $attributes->get('id', $name) }}"
           value="{{ old($name, $value) }}"
           @if ($invalid) aria-invalid="true" aria-describedby="{{ $attributes->get('id', $name) }}-error" @endif
           {{ $attributes->except('id')->class([
               'block min-h-12 w-full rounded-field border bg-white text-[15px] text-navy transition-colors duration-200 placeholder:text-secondary/70 hover:border-line-strong focus:border-navy',
               'pl-11 pr-4' => $icon,
               'px-4' => ! $icon,
               'border-coral-pressed' => $invalid,
               'border-line' => ! $invalid,
           ]) }}>
</div>
