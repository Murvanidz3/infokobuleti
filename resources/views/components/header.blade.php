@php
    $home = localized_route('home');
@endphp

<header x-data="siteHeader"
        x-bind="root"
        class="group/header sticky top-0 z-50 border-b border-white/7 bg-navy transition-[background-color] duration-300 data-[scrolled=true]:bg-navy/86 data-[scrolled=true]:backdrop-blur-[14px] data-[scrolled=true]:backdrop-saturate-[1.6]">
    <div class="container-page flex h-16 items-center gap-7 transition-[height] duration-300 group-data-[scrolled=true]/header:h-14 md:h-[78px] md:group-data-[scrolled=true]/header:h-[62px]">
        <x-logo />

        <nav aria-label="{{ __('nav.main') }}" class="hidden flex-1 justify-center gap-7 xl:flex">
            @foreach (config('site.nav') as $key => $anchor)
                <a href="{{ $home }}#{{ $anchor }}" class="text-[14.5px] font-medium whitespace-nowrap text-white/78 transition-colors duration-200 hover:text-white">{{ __('nav.'.$key) }}</a>
            @endforeach
        </nav>
        <div class="flex-1 xl:hidden"></div>

        <div class="flex shrink-0 items-center gap-3.5">
            <x-language-switcher class="max-md:hidden" />
            <x-add-listing-button class="max-md:hidden" />

            <button type="button"
                    x-bind="menuButton"
                    aria-expanded="false"
                    aria-controls="mobile-drawer"
                    aria-label="{{ __('nav.menu') }}"
                    class="group/menu grid size-11 place-items-center rounded-field border border-white/20 text-white xl:hidden">
                <x-icon name="menu" :size="22" class="group-aria-expanded/menu:hidden" />
                <x-icon name="close" :size="22" class="hidden group-aria-expanded/menu:block" />
            </button>
        </div>
    </div>

    <div id="mobile-drawer" data-drawer class="container-page hidden flex-col gap-0.5 border-t border-white/8 pt-3 pb-6 max-xl:group-data-[open=true]/header:flex">
        @foreach (config('site.nav') as $key => $anchor)
            <a href="{{ $home }}#{{ $anchor }}" class="border-b border-white/8 py-3.5 text-[17px] font-medium text-white">{{ __('nav.'.$key) }}</a>
        @endforeach
        <div class="mt-[18px] flex flex-wrap items-center justify-between gap-3">
            <x-language-switcher size="lg" />
            <x-add-listing-button size="lg" />
        </div>
    </div>
</header>
