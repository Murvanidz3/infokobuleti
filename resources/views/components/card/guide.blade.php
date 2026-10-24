@props([
    'tag',
    'title',
    'meta',
    'description' => null,
    'feature' => false,
    'url' => '#',
    'image' => null,
])

<article {{ $attributes->class(['group relative overflow-hidden rounded-card bg-navy transition-[translate,box-shadow] duration-350 ease-soft hover:shadow-feature-hover motion-safe:hover:-translate-y-1']) }}>
    <x-image :src="$image" :alt="$title" tone="dark" class="media-zoom absolute inset-0" />
    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_top,rgba(10,26,47,0.88)_0%,rgba(10,26,47,0.35)_45%,rgba(10,26,47,0)_70%)]"></div>
    <div @class([
        'absolute inset-x-0 bottom-0 text-white',
        'p-6 md:p-9' => $feature,
        'p-[22px]' => ! $feature,
    ])>
        <x-badge variant="glass">{{ $tag }}</x-badge>
        <h3 @class([
            'mt-3 font-serif leading-[1.25] font-semibold text-white',
            'text-[28px] md:text-[36px]' => $feature,
            'text-[22px]' => ! $feature,
        ])>
            <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $title }}</a>
        </h3>
        @if ($description)
            <p class="mt-3 max-w-[520px] text-[15.5px] leading-[1.65] text-white/85">{{ $description }}</p>
        @endif
        <div class="mt-2.5 flex items-center gap-1.5 text-[13px] text-white/80"><x-icon name="pin" :size="14" />{{ $meta }}</div>
    </div>
</article>
