@props(['paginator'])

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $window = \Illuminate\Pagination\UrlWindow::make($paginator);
    $elements = array_filter([
        $window['first'],
        is_array($window['slider']) ? '…' : null,
        $window['slider'],
        is_array($window['last']) ? '…' : null,
        $window['last'],
    ]);
    $arrow = 'grid size-11 shrink-0 place-items-center rounded-full border border-line-strong bg-white text-navy transition-colors duration-200';
    $page = 'grid h-11 min-w-11 place-items-center rounded-full px-2 text-[14.5px] font-semibold transition-colors duration-200';
@endphp

@if ($paginator->hasPages())
    <nav aria-label="{{ __('components.pagination.label') }}" {{ $attributes->class(['flex items-center justify-center gap-1.5']) }}>
        @if ($paginator->onFirstPage())
            <span class="{{ $arrow }} opacity-40" aria-hidden="true"><x-icon name="chevron-left" :size="18" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('components.pagination.previous') }}" class="{{ $arrow }} hover:border-coral hover:bg-coral hover:text-white">
                <x-icon name="chevron-left" :size="18" />
            </a>
        @endif

        <ul class="flex items-center gap-1">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="grid h-11 min-w-8 place-items-center text-secondary max-xs:hidden" aria-hidden="true">{{ $element }}</li>
                @else
                    @foreach ($element as $number => $url)
                        <li @class(['max-xs:hidden' => abs($number - $paginator->currentPage()) > 1 && $number !== 1 && $number !== $paginator->lastPage()])>
                            @if ($number === $paginator->currentPage())
                                <span aria-current="page" class="{{ $page }} bg-navy text-white">{{ $number }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('components.pagination.page', ['page' => $number]) }}" class="{{ $page }} text-navy hover:bg-surface-alt">{{ $number }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('components.pagination.next') }}" class="{{ $arrow }} hover:border-coral hover:bg-coral hover:text-white">
                <x-icon name="chevron-right" :size="18" />
            </a>
        @else
            <span class="{{ $arrow }} opacity-40" aria-hidden="true"><x-icon name="chevron-right" :size="18" /></span>
        @endif
    </nav>
@endif
