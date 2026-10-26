@props([
    'badge',
    'price',
    'converted' => null,
    'title',
    'location',
    'features' => [],
    'url' => '#',
    'image' => null,
    'saved' => false,
    'favorite' => null,
    'horizontal' => false,
    'eager' => false,
    'promotion' => null,
    'logo' => null,
    'ownerVerified' => false,
])

<article {{ $attributes->class([
    'group card-lift relative overflow-hidden rounded-card border bg-white shadow-card',
    'border-coral' => $promotion,
    'border-line' => ! $promotion,
    'xs:grid xs:grid-cols-[minmax(220px,300px)_1fr]' => $horizontal,
]) }}>
    <div @class(['relative aspect-3/2 overflow-hidden bg-line', 'xs:aspect-auto xs:min-h-[200px]' => $horizontal])>
        <x-image :src="$image" :alt="$title" :loading="$eager ? 'eager' : 'lazy'" class="media-zoom absolute inset-0" />
        <div class="pointer-events-none absolute top-3.5 left-3.5 flex flex-wrap items-center gap-1.5">
            <x-badge variant="navy">{{ $badge }}</x-badge>
            <x-card.promotion-badge :promotion="$promotion" />
        </div>
        @if ($logo)
            <img src="{{ $logo }}" alt="" loading="lazy" class="pointer-events-none absolute bottom-3 left-3 size-11 rounded-chip bg-white object-contain p-1 shadow-card-soft">
        @endif
        <x-favorite-button :saved="$saved" :item="$favorite" class="absolute top-3 right-3" />
    </div>
    <div class="p-5">
        <div class="flex flex-wrap items-baseline gap-2.5">
            <span class="text-[22px] font-bold text-price">{{ $price }}</span>
            @if ($converted)
                <span class="text-[13.5px] text-secondary">{{ $converted }}</span>
            @endif
        </div>
        <h3 class="mt-2 text-[15.5px] leading-normal font-medium text-navy">
            <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $title }}</a>
        </h3>
        <div class="mt-1.5 flex items-center gap-2 text-[13.5px] text-secondary"><x-icon name="pin" :size="16" class="shrink-0 text-navy" /><span class="truncate">{{ $location }}</span></div>
        @if ($ownerVerified)
            <x-badge variant="tint" class="mt-2.5 gap-1"><x-icon name="check-circle" :size="13" />{{ __('site.owner.owner_verified') }}</x-badge>
        @endif
        @if ($features)
            <ul class="mt-4 flex flex-wrap gap-[18px] border-t border-line pt-3.5">
                @foreach ($features as $feature)
                    <li class="flex items-center gap-[7px] text-[13.5px] font-medium text-navy"><x-icon :name="$feature['icon']" :size="17" />{{ $feature['text'] }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
