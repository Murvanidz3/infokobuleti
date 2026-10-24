@props(['locale'])

@foreach (config("locales.supported.{$locale}.preload", []) as $font)
    @php
        try {
            $href = \Illuminate\Support\Facades\Vite::asset($font);
        } catch (\Illuminate\Foundation\ViteException) {
            $href = null;
        }
    @endphp
    @if ($href)
        <link rel="preload" href="{{ $href }}" as="font" type="font/woff2" crossorigin>
    @endif
@endforeach
