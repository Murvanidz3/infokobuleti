@props([
    'name',
    'address',
    'sea' => null,
    'rating' => null,
    'reviews' => null,
    'price' => null,
    'url' => '#',
    'image' => null,
    'saved' => false,
    'favorite' => null,
    'horizontal' => false,
    'eager' => false,
    'promotion' => null,
    'logo' => null,
])

<article {{ $attributes->class([
    'group card-lift relative overflow-hidden rounded-card border bg-white shadow-card',
    'border-coral' => $promotion,
    'border-line' => ! $promotion,
    'xs:grid xs:grid-cols-[minmax(220px,300px)_1fr]' => $horizontal,
]) }}>
    <div @class(['relative aspect-4/3 overflow-hidden bg-line', 'xs:aspect-auto xs:min-h-[200px]' => $horizontal])>
        <x-image :src="$image" :alt="$name" :loading="$eager ? 'eager' : 'lazy'" class="media-zoom absolute inset-0" />
        <x-card.promotion-badge :promotion="$promotion" class="pointer-events-none absolute top-3.5 left-3.5" />
        @if ($logo)
            <img src="{{ $logo }}" alt="" loading="lazy" class="pointer-events-none absolute bottom-3 left-3 size-11 rounded-chip bg-white object-contain p-1 shadow-card-soft">
        @endif
        <x-favorite-button :saved="$saved" :item="$favorite" class="absolute top-3 right-3" />
    </div>
    <div class="flex flex-col px-5 pt-[18px] pb-5">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-[17px] leading-[1.4] font-semibold text-navy">
                <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $name }}</a>
            </h3>
            @if ($rating)
                <span class="shrink-0 rounded-chip bg-navy px-2 py-1 text-[13px] font-bold text-white"><span class="sr-only">{{ __('listing.rating_label') }} </span>{{ $rating }}</span>
            @endif
        </div>
        <div class="mt-2.5 flex flex-col gap-1.5 text-[13.5px] text-secondary">
            <div class="flex items-center gap-2"><x-icon name="pin" :size="16" class="shrink-0 text-navy" /><span class="truncate">{{ $address }}</span></div>
            @if ($sea)
                <div class="flex items-center gap-2"><x-icon name="waves" :size="16" class="shrink-0 text-navy" />{{ $sea }}</div>
            @endif
        </div>
        <div class="min-h-4 grow" aria-hidden="true"></div>
        <div class="flex items-baseline justify-between border-t border-line pt-3.5">
            @if ($price)
                <div><span class="text-xl font-bold text-price">{{ $price }}</span> <span class="text-[13px] text-secondary">{{ __('listing.per_night') }}</span></div>
            @else
                <div class="text-[14px] font-semibold text-secondary">{{ __('site.price.on_request') }}</div>
            @endif
            @if ($reviews)
                <div class="text-[12.5px] text-secondary">{{ $reviews }}</div>
            @endif
        </div>
    </div>
</article>
