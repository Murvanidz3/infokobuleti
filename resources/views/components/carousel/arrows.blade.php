<div {{ $attributes->class(['hidden gap-2 md:flex']) }}>
    <x-icon-button icon="chevron-left" :label="__('ui.previous')" data-direction="-1" x-bind="arrow" />
    <x-icon-button icon="chevron-right" :label="__('ui.next')" data-direction="1" x-bind="arrow" />
</div>
