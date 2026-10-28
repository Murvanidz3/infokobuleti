@props([
    'src' => null,
    'alt' => '',
    'loading' => 'lazy',
    'sizes' => null,
    'srcset' => null,
    'tone' => 'light',
])

@php
    // Gallery "card" images (800 px WebP) also exist as "thumb" (400 px) and a tiny blurred
    // placeholder from the same conversion run: phones get the small file, and the blur shows
    // while the photo loads.
    $placeholder = null;

    if ($src && preg_match('#^(.*/conversions/.+)-card\.webp$#', $src, $match) === 1) {
        $srcset ??= $match[1].'-thumb.webp 400w, '.$src.' 800w';
        $sizes ??= '(min-width: 1100px) 400px, (min-width: 560px) 50vw, 100vw';
        $placeholder = $match[1].'-placeholder.webp';
    }
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="{{ $loading }}" decoding="async"
         @if ($srcset) srcset="{{ $srcset }}" @endif
         @if ($sizes) sizes="{{ $sizes }}" @endif
         @if ($placeholder) style="background-image: url('{{ $placeholder }}'); background-size: cover; background-position: center" @endif
         {{ $attributes->class(['size-full object-cover']) }}>
@else
    <div aria-hidden="true" {{ $attributes->class([
        'grid size-full place-items-center',
        'bg-line text-line-strong' => $tone === 'light',
        'bg-navy-hover/40 text-white/10' => $tone === 'dark',
    ]) }}>
        <x-icon name="image" :size="36" :stroke="1.25" />
    </div>
@endif
