@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class(['border-b border-line bg-surface-alt']) }}>
    <div class="container-page py-[clamp(24px,3.5vw,44px)]">
        @if ($breadcrumbs)
            <x-breadcrumbs :items="$breadcrumbs" class="mb-3" />
        @endif
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="font-serif text-[clamp(28px,3.2vw,42px)] leading-[1.2] font-semibold text-balance text-navy">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2.5 max-w-[680px] text-[clamp(15px,1.15vw,17px)] leading-[1.6] text-secondary">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
