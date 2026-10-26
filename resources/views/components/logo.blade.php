@props(['size' => 'header'])

<a href="{{ localized_route('home') }}" {{ $attributes->class(['flex shrink-0 items-center text-white transition-opacity duration-200 hover:opacity-85']) }}>
    <span class="sr-only">infokobuleti</span>
    <span aria-hidden="true" @class([
        'font-logo leading-none tracking-[-0.025em] whitespace-nowrap',
        'text-[26px]' => $size === 'header',
        'text-[30px]' => $size === 'footer',
    ])><span class="font-light text-white/70">info</span><span class="font-bold text-white">kobulet</span><span class="relative inline-block font-bold text-white">ı<span class="absolute top-[0.14em] left-1/2 -ml-[0.12em] size-[0.24em] rounded-full bg-sun shadow-[0_0_0_0.07em_rgba(232,112,58,0.25),0_0_14px_rgba(232,112,58,0.7)]"></span></span></span>
</a>
