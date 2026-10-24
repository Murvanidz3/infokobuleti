@props([
    'name',
    'count',
    'icon',
    'url' => '#',
])

<a href="{{ $url }}" {{ $attributes->class(['flex h-full min-h-[156px] cursor-pointer flex-col items-center justify-center gap-3.5 rounded-tile border border-line bg-white px-3 py-[22px] text-center text-navy transition-[background-color,color,border-color,translate,box-shadow] duration-250 hover:border-coral hover:bg-coral hover:text-white hover:shadow-tile-hover motion-safe:hover:-translate-y-1']) }}>
    <span class="grid size-[52px] shrink-0 place-items-center rounded-full border border-current"><x-icon :name="$icon" :size="24" /></span>
    <span class="flex w-full min-w-0 flex-col items-center gap-1">
        <span class="block w-full truncate text-sm leading-[1.35] font-semibold">{{ $name }}</span>
        <span class="block w-full truncate text-[13px] leading-[1.35] opacity-65">{{ $count }}</span>
    </span>
</a>
