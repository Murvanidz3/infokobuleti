@props([
    'options',
    'label',
    'selected' => null,
])

{{-- Buttons bind to `option` on the closest x-data="segmented(...)" scope, which sets `value`. --}}
<div role="group" aria-label="{{ $label }}" {{ $attributes->class(['flex gap-1 rounded-full border border-line bg-surface-alt p-1']) }}>
    @foreach ($options as $value => $text)
        <button type="button"
                data-value="{{ $value }}"
                x-bind="option"
                aria-pressed="{{ $value === $selected ? 'true' : 'false' }}"
                class="hit-44 relative rounded-full bg-transparent px-[18px] py-[9px] text-sm font-semibold text-secondary transition-colors duration-250 aria-pressed:bg-navy aria-pressed:text-white">{{ $text }}</button>
    @endforeach
</div>
