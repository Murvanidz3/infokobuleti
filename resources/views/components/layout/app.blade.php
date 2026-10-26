@props([
    'title' => null,
    'description' => null,
    // From StructuredData (detail pages) or ['page' => 'hotels'] (list pages): meta template key,
    // template values, share image, og:type and JSON-LD blocks.
    'seo' => [],
    'noindex' => false,
    'canonical' => null,
])

@php
    $localeRouter = app(\App\Services\LocaleRouter::class);
    $templates = app(\App\Services\Seo\MetaTemplates::class);
    $locale = app()->getLocale();
    $seoPage = $seo['page'] ?? null;
    $seoVars = $seo['vars'] ?? [];
    $pageTitle = $templates->title($seoPage, $seoVars, $title);
    $pageDescription = $templates->description($seoPage, $seoVars, $description, $title);
    $shareImage = $seo['image'] ?? url('/icons/og-default.jpg');
    $page = (int) request()->query('page', 1);
    $canonicalUrl = $canonical ?? url()->current().($page > 1 ? '?page='.$page : '');
    $googleVerification = site_setting('seo.google_verification');
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" data-error-message="{{ __('site.errors.generic') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endif
    @if (filled($googleVerification))
        <meta name="google-site-verification" content="{{ $googleVerification }}">
    @endif
    <meta name="theme-color" content="#0a1a2f">
    @php($pwa = \App\Support\Features::active(\App\Enums\SiteFeature::Pwa))
    @if ($pwa)
        <meta name="pwa" content="1">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="infokobuleti">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($localeRoot = rtrim(localized_route('home'), '/'))
    {{-- Bases of the JSON endpoints; scripts append "/{type}/{id}". --}}
    <meta name="route-favorites" content="{{ $localeRoot }}/favorites">
    <meta name="route-phone" content="{{ $localeRoot }}/phone">
    <meta name="route-suggest" content="{{ localized_route('search.suggest') }}">
    <meta name="route-login" content="{{ localized_route('login') }}">
    <meta property="og:site_name" content="infokobuleti">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="{{ $localeRouter->supported()[$locale]['og_locale'] ?? $locale }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    @unless ($noindex)
        @foreach ($localeRouter->alternates() as $hreflang => $url)
            <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
        @endforeach
    @endunless
    @foreach ($seo['schema'] ?? [] as $schema)
        <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
    <x-layout.font-preload :locale="$locale" />
    @vite('resources/css/app.css')
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-white">
    <a href="#main" class="sr-only z-[60] rounded-button bg-coral-pressed px-4 py-3 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">{{ __('nav.skip_to_content') }}</a>

    <x-impersonation-bar />
    <x-header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-footer />
    <x-toast-stack />
    <x-cookie-consent />
    @if ($pwa)
        <x-install-prompt />
    @endif
    @if (is_array($toast = session('toast')))
        <div x-data x-init="setTimeout(() => window.dispatchEvent(new CustomEvent('toast', { detail: @js($toast) })), 150)"></div>
    @endif

    @guest
        @unless (request()->routeIs('login', '*.login', 'staff.*', '*.staff.*'))
            <x-auth.login-dialog />
        @endunless
    @endguest

    @php($bundle = app(\App\Services\FrontendBundle::class))
    @vite($bundle->script())
    @if ($bundle->usesLivewire())
        @livewireScriptConfig
    @endif
</body>
</html>
