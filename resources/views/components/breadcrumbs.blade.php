@props([
    // Ordered list of ['label' => string, 'url' => ?string]; the last item is the current page.
    'items' => [],
    'tone' => 'light',
])

@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => array_filter([
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
            'item' => $item['url'] ?? null,
        ]))->all(),
    ];
@endphp

<nav aria-label="{{ __('components.breadcrumbs') }}" {{ $attributes }}>
    <ol @class([
        'flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[13px]',
        'text-secondary' => $tone === 'light',
        'text-white/70' => $tone === 'dark',
    ])>
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <x-icon name="chevron-right" :size="14" class="opacity-60" />
                @endif
                @if ($loop->last || empty($item['url']))
                    <span @if ($loop->last) aria-current="page" @endif @class([
                        'font-medium',
                        'text-navy' => $tone === 'light',
                        'text-white' => $tone === 'dark',
                    ])>{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" @class([
                        'transition-colors duration-200',
                        'hover:text-navy' => $tone === 'light',
                        'hover:text-white' => $tone === 'dark',
                    ])>{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</nav>
