@props([
    'label',
    'for' => null,
    'name' => null,
    'hint' => null,
    'error' => null,
    'optional' => false,
])

@php
    $error ??= $name ? $errors->first($name) : null;
    $for ??= $name;
@endphp

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    <label @if ($for) for="{{ $for }}" @endif class="flex items-baseline justify-between gap-3 text-[13.5px] font-semibold text-navy">
        {{ $label }}
        @if ($optional)
            <span class="text-xs font-medium text-secondary">{{ __('components.optional') }}</span>
        @endif
    </label>

    {{ $slot }}

    @if ($error)
        <p @if ($for) id="{{ $for }}-error" @endif class="flex items-center gap-1.5 text-[13px] font-medium text-coral-pressed">
            <x-icon name="alert" :size="15" />{{ $error }}
        </p>
    @elseif ($hint)
        <p @if ($for) id="{{ $for }}-hint" @endif class="text-[13px] text-secondary">{{ $hint }}</p>
    @endif
</div>
