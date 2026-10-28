@php
    // Error pages may render outside the localized routes (unknown URL, crash): take the language
    // from the URL prefix and keep this page free of the session, the database and Livewire.
    $prefix = request()->segment(1);
    $locale = in_array($prefix, ['en', 'ru'], true) ? $prefix : 'ka';
    app()->setLocale($locale);
    $home = $locale === 'ka' ? url('/') : url('/'.$locale);
    $code = $code ?? 500;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('site.errors.'.$code.'.title') }} · infokobuleti</title>
    <meta name="theme-color" content="#0a1a2f">
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen flex-col bg-white">
    <header class="bg-navy">
        <div class="container-page flex h-16 items-center md:h-[78px]">
            <a href="{{ $home }}" aria-label="infokobuleti" class="font-logo text-[26px] leading-none tracking-[-0.025em] text-white"><span class="font-light text-white/70">info</span><span class="font-bold">kobuleti</span></a>
        </div>
    </header>
    <main class="relative flex flex-1 items-center overflow-hidden">
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-[radial-gradient(ellipse_at_bottom,rgba(47,111,159,0.12),transparent_70%)]"></div>
        <div class="container-page relative py-20 text-center">
            <p class="font-serif text-[clamp(72px,12vw,140px)] leading-none font-semibold text-navy/12">{{ $code }}</p>
            <h1 class="mt-2 font-serif text-[clamp(26px,3vw,38px)] font-semibold text-navy">{{ __('site.errors.'.$code.'.title') }}</h1>
            <p class="mx-auto mt-3 max-w-[520px] text-[16px] leading-[1.65] text-secondary">{{ __('site.errors.'.$code.'.text') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ $home }}" class="inline-flex min-h-12 items-center gap-2 rounded-button bg-coral-pressed px-6 text-[15px] font-semibold text-white hover:bg-coral">{{ __('site.errors.home') }}</a>
                @if ($code === 404)
                    <a href="{{ rtrim($home, '/').'/search' }}" class="inline-flex min-h-12 items-center gap-2 rounded-button border border-coral px-6 text-[15px] font-semibold text-navy hover:bg-coral-tint">{{ __('site.search.title') }}</a>
                @endif
            </div>
            @if ($code === 404)
                <nav aria-label="{{ __('nav.main') }}" class="mt-10 flex flex-wrap justify-center gap-x-6 gap-y-2 text-[14.5px]">
                    @foreach (['hotels' => 'hotels', 'real_estate' => 'real-estate', 'classifieds' => 'classifieds', 'jobs' => 'jobs', 'guide' => 'guide', 'news' => 'news'] as $key => $path)
                        <a href="{{ rtrim($home, '/').'/'.$path }}" class="font-medium text-navy hover:text-coral-pressed">{{ __('nav.'.$key) }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </main>
</body>
</html>
