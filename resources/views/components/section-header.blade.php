@props([
    'title',
    'subtitle' => null,
    'href' => null,
    'linkLabel' => null,
    'size' => 'lg',
    'id' => null,
])

@if ($size === 'sm')
    <div {{ $attributes->class(['mb-5 flex items-baseline justify-between gap-4']) }}>
        <h2 @if ($id) id="{{ $id }}" @endif class="font-serif text-[clamp(24px,2vw,30px)] leading-[1.25] font-semibold text-navy">{{ $title }}</h2>
        @if ($href)
            <x-link-arrow :href="$href" size="sm">{{ $linkLabel ?? __('ui.view_all') }}</x-link-arrow>
        @endif
    </div>
@else
    <div {{ $attributes->class(['mb-9 flex flex-wrap items-end justify-between gap-6']) }}>
        <div>
            <h2 @if ($id) id="{{ $id }}" @endif class="font-serif text-[clamp(28px,2.6vw,40px)] leading-[1.2] font-semibold text-navy">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-2.5 max-w-[560px] text-base leading-[1.6] text-secondary">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($href || isset($actions))
            <div class="flex items-center gap-5">
                @if ($href)
                    <x-link-arrow :href="$href">{{ $linkLabel ?? __('ui.view_all') }}</x-link-arrow>
                @endif
                {{ $actions ?? '' }}
            </div>
        @endif
    </div>
@endif
