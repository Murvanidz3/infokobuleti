@props(['title' => null, 'id' => null])

<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['scroll-mt-28 border-t border-line pt-8 first:border-t-0 first:pt-0']) }}>
    @if ($title)
        <h2 class="mb-5 font-serif text-[clamp(21px,1.8vw,25px)] font-semibold text-navy">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
