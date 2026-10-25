@props(['text' => null])

{{-- Owner-written text: escaped, paragraphs kept. --}}
@if (filled($text))
    <div {{ $attributes->class(['flex flex-col gap-3.5 text-[16px] leading-[1.75] text-secondary']) }}>
        @foreach (preg_split('/\R{2,}/u', trim(strip_tags((string) $text))) ?: [] as $paragraph)
            <p>{!! nl2br(e($paragraph)) !!}</p>
        @endforeach
    </div>
@endif
