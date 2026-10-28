@props([
    // Livewire property the inputs are bound to.
    'model',
    'options' => [],
    'multiple' => true,
])

<div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @foreach ($options as $value => $text)
        <label class="cursor-pointer" wire:key="{{ $model }}-{{ $value }}">
            <input type="{{ $multiple ? 'checkbox' : 'radio' }}" wire:model.live="{{ $model }}" value="{{ $value }}" class="peer sr-only">
            <span class="inline-flex min-h-10 items-center gap-1.5 rounded-full border border-line bg-white px-3.5 text-[13.5px] font-semibold text-secondary transition-colors duration-200 hover:border-line-strong hover:text-navy peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ $text }}</span>
        </label>
    @endforeach
</div>
