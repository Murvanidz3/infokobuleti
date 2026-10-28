@props([
    'title',
    'meta',
    'price',
    'time',
    'icon' => 'tag',
    'isNew' => false,
    'url' => '#',
    'image' => null,
    'saved' => false,
    'favorite' => null,
    'promotion' => null,
])

{{-- Photo variant of the classified card, for the grid view of the classifieds page. --}}
<article {{ $attributes->class(['group card-lift relative flex flex-col overflow-hidden rounded-card border bg-white shadow-card', 'border-coral' => $promotion, 'border-line' => ! $promotion]) }}>
    <div class="relative aspect-4/3 overflow-hidden bg-line">
        @if ($image)
            <x-image :src="$image" :alt="$title" class="media-zoom absolute inset-0" />
        @else
            <div class="absolute inset-0 grid place-items-center text-navy/25" aria-hidden="true"><x-icon :name="$icon" :size="44" :stroke="1.25" /></div>
        @endif
        @if ($promotion)
            <x-card.promotion-badge :promotion="$promotion" class="pointer-events-none absolute top-3.5 left-3.5" />
        @elseif ($isNew)
            <x-badge variant="new" class="pointer-events-none absolute top-3.5 left-3.5">{{ __('ui.new') }}</x-badge>
        @endif
        <x-favorite-button :saved="$saved" :item="$favorite" class="absolute top-3 right-3" />
    </div>
    <div class="flex grow flex-col p-4">
        <span class="text-[19px] font-bold text-price">{{ $price }}</span>
        <h3 class="mt-1 line-clamp-2 text-[15px] leading-snug font-medium text-navy">
            <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $title }}</a>
        </h3>
        <div class="min-h-3 grow" aria-hidden="true"></div>
        <div class="flex items-center justify-between gap-3 text-[12.5px] text-secondary">
            <span class="truncate">{{ $meta }}</span>
            <span class="shrink-0">{{ $time }}</span>
        </div>
    </div>
</article>
