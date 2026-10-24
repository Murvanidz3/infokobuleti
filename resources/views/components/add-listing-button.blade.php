@props(['size' => 'sm', 'href' => '#'])

<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex items-center gap-2.5 rounded-full bg-coral-pressed text-sm font-semibold whitespace-nowrap text-white shadow-cta transition-[translate,background-color,box-shadow] duration-250 ease-soft hover:bg-coral hover:text-white hover:shadow-cta-hover motion-safe:hover:-translate-y-0.5',
    'py-1.5 pr-[18px] pl-1.5' => $size === 'sm',
    'py-[7px] pr-5 pl-[7px]' => $size === 'lg',
]) }}>
    <span @class([
        'grid place-items-center rounded-full bg-white text-coral',
        'size-7' => $size === 'sm',
        'size-[30px]' => $size === 'lg',
    ])>
        <x-icon name="plus" :size="15" :stroke="2.2" />
    </span>
    {{ __('nav.add_listing') }}
</a>
