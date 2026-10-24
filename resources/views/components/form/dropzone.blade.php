@props([
    'name',
    'accept' => 'image/jpeg,image/png,image/webp',
    'multiple' => true,
])

@php
    $id = $attributes->get('id', $name);
@endphp

<div x-data="dropzone" {{ $attributes->except('id')->class(['flex flex-col gap-3']) }}>
    <div x-on:dragover.prevent="dragging = true"
         x-on:dragleave.prevent="dragging = false"
         x-on:drop.prevent="drop($event)"
         x-bind:class="{ 'border-coral bg-coral-tint': dragging, 'border-line-strong bg-surface-alt': ! dragging }"
         class="flex flex-col items-center gap-3 rounded-card border-[1.5px] border-dashed border-line-strong bg-surface-alt px-6 py-10 text-center transition-colors duration-200">
        <span class="grid size-14 place-items-center rounded-full bg-white text-navy shadow-card">
            <x-icon name="upload" :size="24" />
        </span>
        <p class="text-base font-semibold text-navy">{{ __('components.dropzone.title') }}</p>
        <p class="text-[13px] text-secondary">{{ __('components.dropzone.or') }}</p>
        <input type="file" id="{{ $id }}" name="{{ $multiple ? $name.'[]' : $name }}" accept="{{ $accept }}" @if ($multiple) multiple @endif
               x-ref="input" x-on:change="sync()" class="peer sr-only">
        <label for="{{ $id }}" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-button border border-coral bg-white px-5 text-[14.5px] font-semibold text-navy transition-colors duration-200 hover:bg-coral-tint peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">
            {{ __('components.dropzone.button') }}
        </label>
        <p class="text-xs text-secondary">{{ __('components.dropzone.hint') }}</p>
    </div>

    <ul x-show="files.length" x-cloak class="flex flex-col divide-y divide-line rounded-card border border-line bg-white">
        <template x-for="file in files" :key="file.name">
            <li class="flex items-center gap-3 px-4 py-3 text-sm">
                <x-icon name="image" :size="18" class="text-secondary" />
                <span class="min-w-0 flex-1 truncate font-medium text-navy" x-text="file.name"></span>
                <span class="text-xs text-secondary" x-text="file.size"></span>
            </li>
        </template>
    </ul>
</div>
