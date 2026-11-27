{{-- Multiple choice as pills, bound to a Livewire array property. --}}
@props(['model', 'options' => [], 'label' => null])

<div role="group" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @foreach ($options as $value => $text)
        <label class="group cursor-pointer" wire:key="{{ $model }}-{{ $value }}">
            <input type="checkbox" value="{{ $value }}" wire:model.live="{{ $model }}" class="sr-only">
            <span class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-line bg-white px-4 text-sm font-semibold text-navy transition-colors duration-200 group-hover:border-line-strong group-has-[:checked]:border-navy group-has-[:checked]:bg-navy group-has-[:checked]:text-white group-has-[:focus-visible]:outline-2 group-has-[:focus-visible]:outline-offset-2 group-has-[:focus-visible]:outline-coral">
                {{ $text }}
            </span>
        </label>
    @endforeach
</div>
