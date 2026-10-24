@props([
    'background' => 'white',
    'spacing' => 'default',
    'reveal' => true,
])

<section {{ $attributes->class([
    'bg-white' => $background === 'white',
    'bg-surface-alt' => $background === 'alt',
    'bg-navy' => $background === 'navy',
    'section-y' => $spacing === 'default',
    'py-[clamp(48px,5vw,72px)]' => $spacing === 'compact',
    'py-[clamp(40px,4vw,56px)]' => $spacing === 'tight',
    'py-[clamp(64px,7vw,104px)]' => $spacing === 'banner',
]) }}>
    <div @if ($reveal) data-reveal @endif class="container-page">
        {{ $slot }}
    </div>
</section>
