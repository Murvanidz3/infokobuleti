@props([
    'name',
    'address',
    'sea',
    'rating',
    'reviews',
    'price',
    'url' => '#',
    'image' => null,
    'saved' => false,
])

<article {{ $attributes->class(['group card-lift relative overflow-hidden rounded-card border border-line bg-white shadow-card']) }}>
    <div class="relative aspect-4/3 overflow-hidden bg-line">
        <x-image :src="$image" :alt="$name" class="media-zoom absolute inset-0" />
        <x-favorite-button :saved="$saved" class="absolute top-3 right-3" />
    </div>
    <div class="px-5 pt-[18px] pb-5">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-[17px] leading-[1.4] font-semibold text-navy">
                <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $name }}</a>
            </h3>
            <span class="shrink-0 rounded-chip bg-navy px-2 py-1 text-[13px] font-bold text-white"><span class="sr-only">{{ __('listing.rating_label') }} </span>{{ $rating }}</span>
        </div>
        <div class="mt-2.5 flex flex-col gap-1.5 text-[13.5px] text-secondary">
            <div class="flex items-center gap-2"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</div>
            <div class="flex items-center gap-2"><x-icon name="waves" :size="16" class="text-navy" />{{ $sea }}</div>
        </div>
        <div class="mt-4 flex items-baseline justify-between border-t border-line pt-3.5">
            <div><span class="text-xl font-bold text-price">{{ $price }}</span> <span class="text-[13px] text-secondary">{{ __('listing.per_night') }}</span></div>
            <div class="text-[12.5px] text-secondary">{{ $reviews }}</div>
        </div>
    </div>
</article>
