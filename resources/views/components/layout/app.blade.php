@props([
    'title' => null,
    'description' => null,
])

@php
    $localeRouter = app(\App\Services\LocaleRouter::class);
    $locale = app()->getLocale();
    $pageTitle = $title ? $title.' · infokobuleti' : __('meta.title');
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" data-error-message="{{ __('site.errors.generic') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description ?? __('meta.description') }}">
    <meta name="theme-color" content="#0a1a2f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($localeRoot = rtrim(localized_route('home'), '/'))
    {{-- Bases of the JSON endpoints; scripts append "/{type}/{id}". --}}
    <meta name="route-favorites" content="{{ $localeRoot }}/favorites">
    <meta name="route-phone" content="{{ $localeRoot }}/phone">
    <meta name="route-suggest" content="{{ localized_route('search.suggest') }}">
    <meta name="route-login" content="{{ localized_route('login') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description ?? __('meta.description') }}">
    <meta property="og:locale" content="{{ $localeRouter->supported()[$locale]['og_locale'] ?? $locale }}">
    @foreach ($localeRouter->alternates() as $hreflang => $url)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
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
