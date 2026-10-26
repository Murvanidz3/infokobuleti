@props([
    'tag',
    'title',
    'day',
    'month',
    'when',
    'venue',
    'price' => null,
    'url' => '#',
    'image' => null,
])

<article {{ $attributes->class(['group card-lift relative flex flex-col overflow-hidden rounded-card border border-line bg-white shadow-card']) }}>
    <div class="relative aspect-16/9 overflow-hidden bg-line">
        <x-image :src="$image" :alt="$title" class="media-zoom absolute inset-0" />
        <div class="absolute top-3 left-3 flex min-w-14 flex-col items-center rounded-tile bg-white/95 px-2.5 py-1.5 text-navy shadow-card">
            <span class="text-[22px] leading-none font-bold">{{ $day }}</span>
            <span class="mt-0.5 text-[11.5px] font-semibold uppercase tracking-wide text-coral-pressed">{{ $month }}</span>
        </div>
    </div>
    <div class="flex grow flex-col p-5">
        <span class="text-[12.5px] font-semibold tracking-wide text-coral-pressed uppercase">{{ $tag }}</span>
        <h3 class="mt-1.5 text-[17px] leading-[1.4] font-semibold text-navy">
            <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $title }}</a>
        </h3>
        <div class="mt-2.5 flex flex-col gap-1.5 text-[13.5px] text-secondary">
            <div class="flex items-center gap-2"><x-icon name="clock" :size="16" class="shrink-0 text-navy" />{{ $when }}</div>
            <div class="flex items-center gap-2"><x-icon name="pin" :size="16" class="shrink-0 text-navy" /><span class="truncate">{{ $venue }}</span></div>
        </div>
        @if ($price)
            <div class="min-h-3 grow" aria-hidden="true"></div>
            <div class="border-t border-line pt-3 text-[14px] font-semibold text-navy">{{ $price }}</div>
        @endif
    </div>
</article>
