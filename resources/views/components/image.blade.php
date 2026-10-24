@props([
    'src' => null,
    'alt' => '',
    'loading' => 'lazy',
    'sizes' => null,
    'srcset' => null,
    'tone' => 'light',
])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="{{ $loading }}" decoding="async"
         @if ($srcset) srcset="{{ $srcset }}" @endif
         @if ($sizes) sizes="{{ $sizes }}" @endif
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
