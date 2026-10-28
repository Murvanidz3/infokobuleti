@props(['size' => 'sm', 'href' => null, 'collapse' => false])

{{-- collapse: icon only while the full desktop navigation is shown on narrower screens (1240–1599px). --}}

<a href="{{ $href ?? localized_route('listings.create') }}" @if ($collapse) title="{{ __('nav.add_listing') }}" @endif {{ $attributes->class([
    'inline-flex items-center gap-2.5 rounded-full bg-coral-pressed text-sm font-semibold whitespace-nowrap text-white shadow-cta transition-[translate,background-color,box-shadow] duration-250 ease-soft hover:bg-coral hover:text-white hover:shadow-cta-hover motion-safe:hover:-translate-y-0.5',
    'py-1.5 pr-[18px] pl-1.5' => $size === 'sm' && ! $collapse,
    'py-1.5 pr-[18px] pl-1.5 xl:max-[1599px]:pr-1.5' => $size === 'sm' && $collapse,
    'py-[7px] pr-5 pl-[7px]' => $size === 'lg',
]) }}>
    <span @class([
        'grid place-items-center rounded-full bg-white text-coral',
        'size-7' => $size === 'sm',
        'size-[30px]' => $size === 'lg',
    ])>
        <x-icon name="plus" :size="15" :stroke="2.2" />
    </span>
    <span @class(['xl:max-[1599px]:sr-only' => $collapse])>{{ __('nav.add_listing') }}</span>
</a>
