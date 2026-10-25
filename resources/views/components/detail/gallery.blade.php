@props([
    'images' => [],
    'title',
])

@php
    $count = count($images);
    $shown = min($count, 5);
    // Mosaic cells on a 4×2 grid by number of photos: the first is always the big one.
    $cell = fn (int $index): string => match (true) {
        $shown === 1 => 'md:col-span-4 md:row-span-2',
        $index === 0 => 'md:col-span-2 md:row-span-2',
        $shown === 2 => 'md:col-span-2 md:row-span-2',
        $shown === 3, $shown === 4 && $index === 1 => 'md:col-span-2',
        default => '',
    };
@endphp

{{-- Up to five photos in a mosaic (a swipeable strip on phones); every photo opens the lightbox. --}}
<div x-data="gallery({{ $count }})" {{ $attributes->class(['relative']) }}>
    @if ($count === 0)
        <div class="aspect-16/9 overflow-hidden rounded-card md:aspect-[21/9]">
            <x-image :src="null" :alt="$title" />
        </div>
    @else
        <div class="flex snap-x snap-mandatory gap-2 overflow-x-auto rounded-card scrollbar-none md:grid md:aspect-[21/9] md:grid-cols-4 md:grid-rows-2 md:overflow-hidden">
            @foreach (array_slice($images, 0, 5) as $index => $image)
                <button type="button" x-on:click="show({{ $index }})"
                        aria-label="{{ __('site.gallery.open', ['number' => $index + 1, 'total' => $count]) }}"
                        @class(['group relative aspect-4/3 shrink-0 snap-start overflow-hidden bg-line md:aspect-auto md:w-auto', $count > 1 ? 'w-[86%]' : 'w-full', $cell($index)])>
                    <img src="{{ $image['thumb'] }}" alt="{{ $index === 0 ? $title : '' }}" @if ($index === 0) fetchpriority="high" loading="eager" @else loading="lazy" @endif decoding="async"
                         class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-103">
                </button>
            @endforeach
        </div>
        @if ($count > 1)
            <button type="button" x-on:click="show(0)" class="absolute right-3 bottom-3 inline-flex min-h-10 items-center gap-2 rounded-button bg-white/95 px-3.5 text-[13.5px] font-semibold text-navy shadow-card-soft backdrop-blur hover:bg-white">
                <x-icon name="image" :size="16" />
                {{ trans_choice('site.gallery.all', $count, ['count' => $count]) }}
            </button>
        @endif

        <template x-teleport="body">
            <div x-show="open" x-transition.opacity x-on:keydown.escape.window="close()" x-on:keydown.right.window="open && next()" x-on:keydown.left.window="open && previous()"
                 role="dialog" aria-modal="true" aria-label="{{ $title }}" class="fixed inset-0 z-[90] flex flex-col bg-navy/96" style="display: none">
                <div class="flex items-center justify-between px-4 py-3 text-white">
                    <span class="text-[14px] font-semibold" x-text="`${index + 1} / ${count}`"></span>
                    <x-icon-button icon="close" :label="__('components.dialog.close')" variant="inverse" x-on:click="close()" />
                </div>
                <div x-ref="track" x-on:scroll.debounce.60ms="onScroll()" class="flex flex-1 snap-x snap-mandatory overflow-x-auto scrollbar-none">
                    @foreach ($images as $image)
                        <div class="grid w-full shrink-0 snap-center place-items-center p-2 md:p-8">
                            <img x-bind:src="open ? @js($image['large']) : ''" alt="" class="max-h-full max-w-full rounded-tile object-contain">
                        </div>
                    @endforeach
                </div>
                <div class="pointer-events-none absolute inset-x-0 top-1/2 hidden -translate-y-1/2 justify-between px-4 md:flex">
                    <x-icon-button icon="chevron-left" :label="__('site.gallery.previous')" variant="inverse" class="pointer-events-auto bg-navy/60" x-on:click="previous()" />
                    <x-icon-button icon="chevron-right" :label="__('site.gallery.next')" variant="inverse" class="pointer-events-auto bg-navy/60" x-on:click="next()" />
                </div>
            </div>
        </template>
    @endif
</div>
