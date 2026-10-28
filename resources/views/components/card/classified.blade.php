@props([
    'title',
    'meta',
    'price',
    'time',
    'icon' => 'tag',
    'isNew' => false,
    'url' => '#',
    'promotion' => null,
])

<a href="{{ $url }}" {{ $attributes->class(['flex items-center gap-4 border-t border-line px-5 py-4 transition-colors duration-200 first:border-t-0 hover:bg-surface-alt']) }}>
    <span class="grid size-12 shrink-0 place-items-center rounded-button bg-line text-navy"><x-icon :name="$icon" :size="22" /></span>
    <span class="min-w-0 flex-1">
        <span class="flex min-w-0 items-center gap-2">
            <span class="truncate text-[15px] font-semibold text-navy">{{ $title }}</span>
            <x-card.promotion-badge :promotion="$promotion" />
            @if ($isNew && ! $promotion)
                <x-badge variant="new">{{ __('ui.new') }}</x-badge>
            @endif
        </span>
        <span class="mt-1 block truncate text-[13px] text-secondary">{{ $meta }}</span>
    </span>
    <span class="shrink-0 text-right">
        <span class="block text-[15px] font-bold whitespace-nowrap text-navy">{{ $price }}</span>
        <span class="mt-1 block text-[12.5px] whitespace-nowrap text-secondary">{{ $time }}</span>
    </span>
</a>
