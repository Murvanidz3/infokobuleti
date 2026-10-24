@props(['size' => 'sm'])

@php
    $localeRouter = app(\App\Services\LocaleRouter::class);
@endphp

<nav aria-label="{{ __('nav.language') }}" {{ $attributes->class(['flex gap-0.5 rounded-full border border-white/18 p-[3px]']) }}>
    @foreach ($localeRouter->supported() as $locale => $meta)
        @php($active = $locale === app()->getLocale())
        <a href="{{ $localeRouter->switchUrl($locale) }}"
           hreflang="{{ $meta['hreflang'] }}"
           lang="{{ $locale }}"
           @if ($active) aria-current="true" @endif
           @class([
               'hit-44 relative rounded-full font-semibold tracking-[0.04em] transition-colors duration-200',
               'px-2.5 py-[5px] text-xs' => $size === 'sm',
               'px-3.5 py-2 text-[13px]' => $size === 'lg',
               'bg-white text-navy' => $active,
               'text-white/75 hover:text-white' => ! $active,
           ])>{{ $meta['code'] }}<span class="sr-only"> {{ __('locales.'.$locale) }}</span></a>
    @endforeach
</nav>
