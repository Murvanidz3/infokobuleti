@props([
    'image' => null,
    'overlay' => 0.7,
    'searchOptions' => ['classifieds' => [], 'jobs' => []],
])

@php
    $navy = fn (float $alpha): string => 'rgba(10,26,47,'.number_format($alpha, 3, '.', '').')';
    $gradient = "linear-gradient(to top, {$navy($overlay)} 0%, {$navy($overlay * 0.72)} 45%, {$navy($overlay * 0.43)} 100%)";
@endphp

<section {{ $attributes->class(['relative flex min-h-[clamp(560px,48vw,720px)] items-end bg-navy']) }}>
    @if ($image)
        <div class="absolute inset-0 overflow-hidden">
            <x-image :src="$image" alt="" loading="eager" fetchpriority="high" />
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

        <x-hero.search :options="$searchOptions" class="mt-[clamp(28px,3.5vw,44px)] hidden md:block" />
        <x-hero.compact-search class="mt-7 md:hidden" />
    </div>
</section>
