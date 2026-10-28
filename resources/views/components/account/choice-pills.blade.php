{{-- Single choice as pills, bound to a Livewire property (wire:model.live). --}}
@props(['model', 'options' => [], 'label' => null])

<div role="radiogroup" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @foreach ($options as $value => $text)
        <label class="cursor-pointer" wire:key="{{ $model }}-{{ $value }}">
            <input type="radio" value="{{ $value }}" wire:model.live="{{ $model }}" class="peer sr-only">
            <span class="inline-flex min-h-11 items-center rounded-full border border-line bg-white px-[18px] text-sm font-semibold text-secondary transition-colors duration-200 hover:border-line-strong hover:text-navy peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ $text }}</span>
        </label>
    @endforeach
</div>
