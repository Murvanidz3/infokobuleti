@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'wide' => false,
])

{{-- Centered panel for sign-in, staff and onboarding screens. --}}
<section class="bg-surface-alt py-8 md:py-16">
    <div class="container-page">
        <div @class([
            'mx-auto w-full rounded-panel border border-line bg-white p-6 shadow-card-soft md:p-9',
            'max-w-[460px]' => ! $wide,
            'max-w-[640px]' => $wide,
        ])>
            @if ($icon)
                <span class="mb-5 grid size-12 place-items-center rounded-full bg-coral-tint text-coral-pressed">
                    <x-icon :name="$icon" :size="22" />
                </span>
            @endif
            <h1 class="font-serif text-[26px] leading-tight font-semibold text-navy md:text-[28px]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-2.5 text-[15px] leading-[1.6] text-secondary">{{ $subtitle }}</p>
            @endif

            <x-auth.flash class="mt-5" />

            <div class="mt-6">
                {{ $slot }}
            </div>
        </div>

        @isset($after)
            <div class="mx-auto mt-5 w-full max-w-[460px] text-center text-sm text-secondary">{{ $after }}</div>
        @endisset
    </div>
</section>
