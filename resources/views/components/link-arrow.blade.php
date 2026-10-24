@props(['href', 'size' => 'md'])

<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex items-center gap-2 font-semibold whitespace-nowrap text-navy transition-[gap,color] duration-200 hover:gap-3 hover:text-coral',
    'text-[14.5px]' => $size === 'md',
    'text-sm' => $size === 'sm',
]) }}>
    {{ $slot }}
    <x-icon name="arrow-right" :size="$size === 'sm' ? 16 : 18" />
</a>
