@props(['label' => null])

{{-- Place inside an element with x-data="carousel" so the arrow buttons can reach the track.
     Pass x-ignore when the slides contain no Alpine directives, so Alpine skips walking them at startup. --}}
<div data-carousel-track
     @if ($label) role="region" aria-label="{{ $label }}" @endif
     tabindex="0"
     {{ $attributes->class(['-m-1.5 -mb-7 flex snap-x snap-mandatory scroll-px-1.5 gap-6 overflow-x-auto p-1.5 pb-7 scrollbar-none focus-visible:outline-offset-[-2px]']) }}>
    {{ $slot }}
</div>
