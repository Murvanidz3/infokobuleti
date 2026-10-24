@props(['icon', 'label'])

<div {{ $attributes->class(['rounded-tile border border-line bg-white p-5']) }}>
    <div class="flex items-center gap-2.5 text-[13px] font-medium text-secondary">
        <span class="grid size-[34px] place-items-center rounded-full bg-line text-navy"><x-icon :name="$icon" :size="18" /></span>
        {{ $label }}
    </div>
    <div class="mt-3.5">
        {{ $slot }}
    </div>
</div>
