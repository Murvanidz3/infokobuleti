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
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description ?? __('meta.description') }}">
    <meta name="theme-color" content="#0a1a2f">
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

    <x-header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-footer />
    <x-toast-stack />

    @php($bundle = app(\App\Services\FrontendBundle::class))
    @vite($bundle->script())
    @if ($bundle->usesLivewire())
        @livewireScriptConfig
    @endif
</body>
</html>
