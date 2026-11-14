@props([
    'image' => null,
    'video' => null,
    'overlay' => 0.7,
    'categories' => [],
])

@php
    $navy = fn (float $alpha): string => 'rgba(10,26,47,'.number_format($alpha, 3, '.', '').')';
    $gradient = "linear-gradient(to top, {$navy($overlay)} 0%, {$navy($overlay * 0.72)} 45%, {$navy($overlay * 0.43)} 100%)";
@endphp

<section {{ $attributes->class(['relative flex min-h-[clamp(560px,48vw,720px)] items-end bg-navy']) }}>
    @if ($image || $video)
        <div class="absolute inset-0 overflow-hidden">
            <x-image :src="$image" alt="" loading="eager" fetchpriority="high" />
            @if ($video)
                <video autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1" disablepictureinpicture
                       class="absolute inset-0 size-full scale-[1.04] object-cover blur-[3px] motion-reduce:hidden">
                    <source src="{{ $video }}" type="video/mp4" media="(prefers-reduced-motion: no-preference)">
                </video>
            @endif
        </div>
    @endif
    <div class="pointer-events-none absolute inset-0" style="background: {{ $gradient }}"></div>

    <div class="container-page relative pt-[clamp(80px,9vw,140px)] pb-[clamp(40px,5vw,72px)]">
        <p class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-white/85">
            <x-icon name="pin" :size="16" />
            {{ __('home.hero.location') }}
        </p>
        <h1 class="max-w-[880px] font-serif text-[clamp(34px,4.5vw,66px)] leading-[1.18] font-semibold tracking-[-0.01em] text-balance text-white">{{ __('home.hero.title') }}</h1>
        <p class="mt-5 max-w-[620px] text-[clamp(16px,1.3vw,19px)] leading-[1.65] text-pretty text-white/84">{{ __('home.hero.subtitle') }}</p>

        <x-hero.compact-search class="mt-7 md:hidden" />

        @if ($categories !== [])
            <h2 id="categories-title" class="sr-only">{{ __('home.categories.title') }}</h2>
            <ul data-hero-categories aria-labelledby="categories-title" class="mt-4 grid grid-cols-2 gap-2.5 md:mt-[clamp(28px,3.5vw,44px)] xs:grid-cols-4 xs:gap-3.5 xl:grid-cols-8">
                @foreach ($categories as $index => $category)
                    <li class="tile-rise min-w-0" style="animation-delay: {{ $index * 60 }}ms">
                        <x-category-tile variant="glass" :name="$category['name']" :count="$category['count']" :icon="$category['icon']" :url="$category['url']" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
