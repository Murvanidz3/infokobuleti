@props([
    'name',
    'count',
    'icon',
    'url' => '#',
    'variant' => 'default',
])

@if ($variant === 'glass')
    <a href="{{ $url }}" data-category-tile {{ $attributes->class(['group relative block h-full w-full min-w-0 rounded-tile']) }}>
        <span class="relative flex h-full min-h-[72px] w-full cursor-pointer flex-row items-center gap-3 rounded-tile border border-white/28 bg-white/14 py-2.5 pr-8 pl-3 text-left text-white backdrop-blur-[18px] backdrop-saturate-[1.4] transition-[background-color,border-color,translate] duration-300 ease-soft group-hover:border-coral group-hover:bg-coral-pressed group-focus-visible:border-coral group-focus-visible:bg-coral-pressed motion-safe:group-hover:-translate-y-1.5 motion-safe:group-active:translate-y-0 xs:min-h-[160px] xs:flex-col xs:justify-center xs:gap-3.5 xs:px-3 xs:py-4 xs:text-center">
            <span class="grid size-12 shrink-0 place-items-center rounded-chip bg-white/20 text-white transition-[background-color,color,scale] duration-300 ease-soft group-hover:bg-white group-hover:text-coral-pressed group-focus-visible:bg-white group-focus-visible:text-coral-pressed motion-safe:group-hover:scale-110 xs:size-14 xs:rounded-button">
                <x-icon :name="$icon" :size="28" />
            </span>
            <span class="flex w-full min-w-0 flex-1 flex-col items-start gap-0.5 xs:flex-none xs:items-center">
                <span class="block w-full truncate text-sm font-semibold xs:text-[15px] xs:whitespace-normal xl:text-sm">{{ $name }}</span>
                <span class="block w-full truncate text-[13px] text-white/80 transition-colors group-hover:text-white group-focus-visible:text-white">{{ $count }}</span>
            </span>
            <x-icon name="arrow-right" :size="16" class="absolute top-2.5 right-2.5 opacity-0 transition-[opacity,translate] duration-250 group-hover:opacity-100 group-focus-visible:opacity-100 motion-safe:translate-x-1 motion-safe:group-hover:translate-x-0 motion-safe:group-focus-visible:translate-x-0" />
        </span>
    </a>
@else
    <a href="{{ $url }}" {{ $attributes->class(['flex h-full min-h-[156px] cursor-pointer flex-col items-center justify-center gap-3.5 rounded-tile border border-line bg-white px-3 py-[22px] text-center text-navy transition-[background-color,color,border-color,translate,box-shadow] duration-250 hover:border-coral hover:bg-coral hover:text-white hover:shadow-tile-hover motion-safe:hover:-translate-y-1']) }}>
        <span class="grid size-[52px] shrink-0 place-items-center rounded-full border border-current"><x-icon :name="$icon" :size="24" /></span>
        <span class="flex w-full min-w-0 flex-col items-center gap-1">
            <span class="block w-full truncate text-sm leading-[1.35] font-semibold">{{ $name }}</span>
            <span class="block w-full truncate text-[13px] leading-[1.35] opacity-65">{{ $count }}</span>
        </span>
    </a>
@endif
