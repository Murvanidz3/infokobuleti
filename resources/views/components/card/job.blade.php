@props([
    'title',
    'employer',
    'salary',
    'period',
    'type',
    'seasonal' => false,
    'posted',
    'url' => '#',
])

<a href="{{ $url }}" {{ $attributes->class(['flex items-start gap-4 border-t border-line px-5 py-4 transition-colors duration-200 first:border-t-0 hover:bg-surface-alt']) }}>
    <span class="grid size-12 shrink-0 place-items-center rounded-button bg-line text-navy"><x-icon name="briefcase" :size="22" /></span>
    <span class="min-w-0 flex-1">
        <span class="block truncate text-[15px] font-semibold text-navy">{{ $title }}</span>
        <span class="mt-[3px] block truncate text-[13px] text-secondary">{{ $employer }}</span>
        <span class="mt-2 flex flex-wrap items-center gap-2.5">
            <x-badge :variant="$seasonal ? 'outline' : 'outline-tint'">{{ $type }}</x-badge>
            <span class="inline-flex items-center gap-[5px] text-[12.5px] text-secondary"><x-icon name="clock" :size="14" />{{ $posted }}</span>
        </span>
    </span>
    <span class="shrink-0 text-right">
        <span class="block text-[15px] font-bold whitespace-nowrap text-navy">{{ $salary }}</span>
        <span class="mt-[3px] block text-[12.5px] text-secondary">{{ $period }}</span>
    </span>
</a>
