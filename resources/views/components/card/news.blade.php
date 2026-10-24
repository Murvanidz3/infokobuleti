@props([
    'tag',
    'date',
    'title',
    'url' => '#',
    'image' => null,
])

<article {{ $attributes->class(['group card-lift relative overflow-hidden rounded-card border border-line bg-white shadow-card']) }}>
    <div class="relative aspect-16/10 overflow-hidden bg-line">
        <x-image :src="$image" :alt="$title" class="media-zoom absolute inset-0" />
    </div>
    <div class="px-[22px] pt-5 pb-6">
        <div class="flex flex-wrap items-center gap-3">
            <x-badge variant="tint">{{ $tag }}</x-badge>
            <span class="text-[13px] text-secondary">{{ $date }}</span>
        </div>
        <h3 class="mt-3.5 text-lg leading-normal font-semibold text-pretty text-navy">
            <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $title }}</a>
        </h3>
    </div>
</article>
