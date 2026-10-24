@props([
    'title',
    'text' => null,
    'action',
    'href' => '#',
])

<x-section background="navy" spacing="banner" {{ $attributes }}>
    <div class="flex flex-wrap items-center justify-between gap-8">
        <div class="max-w-[720px]">
            <h2 class="font-serif text-[clamp(28px,3vw,44px)] leading-[1.25] font-semibold text-balance text-white">{{ $title }}</h2>
            @if ($text)
                <p class="mt-4 text-[17px] leading-[1.65] text-pretty text-white/75">{{ $text }}</p>
            @endif
        </div>
        <x-button :href="$href" size="lg" icon-right="arrow-right" class="py-4">{{ $action }}</x-button>
    </div>
</x-section>
