@props([
    'reviews' => [],
    'rating' => null,
    'count' => 0,
])

<div {{ $attributes }}>
    @if ($rating)
        <div class="mb-5 flex items-center gap-3">
            <span class="rounded-chip bg-navy px-2.5 py-1.5 text-[17px] font-bold text-white">{{ $rating }}</span>
            <span class="text-[14.5px] text-secondary">{{ trans_choice('ui.counts.reviews', $count, ['count' => $count]) }}</span>
        </div>
    @endif
    @if ($reviews)
        <ul class="grid gap-4 md:grid-cols-2">
            @foreach ($reviews as $review)
                <li class="rounded-card border border-line bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-full bg-surface-alt text-[14px] font-semibold text-navy">{{ $review['initials'] }}</span>
                            <div>
                                <p class="text-[14.5px] font-semibold text-navy">{{ $review['author'] }}</p>
                                <p class="text-[12.5px] text-secondary">{{ $review['date'] }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[14px] font-bold text-navy"><x-icon name="star" :size="15" class="fill-sun text-sun" />{{ $review['rating'] }}</span>
                    </div>
                    @if ($review['body'])
                        <p class="mt-3 text-[14.5px] leading-[1.65] text-secondary">{{ $review['body'] }}</p>
                    @endif
                    @if ($review['reply'])
                        <div class="mt-3 rounded-tile bg-surface-alt p-3 text-[13.5px] leading-[1.6] text-secondary">
                            <p class="mb-1 font-semibold text-navy">{{ __('site.reviews.reply') }}</p>
                            {{ $review['reply'] }}
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-[15px] text-secondary">{{ __('site.reviews.none') }}</p>
    @endif
</div>
