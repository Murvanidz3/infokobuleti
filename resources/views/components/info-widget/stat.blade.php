@props([
    'value',
    'caption' => null,
    'size' => 'lg',
    'tone' => 'navy',
])

<div {{ $attributes }}>
    <div @class([
        'leading-none font-semibold',
        'text-[30px]' => $size === 'xl',
        'text-[26px]' => $size === 'lg',
        'text-navy' => $tone === 'navy',
        'text-sea' => $tone === 'sea',
    ])>{{ $value }}</div>
    @if ($caption)
        <div class="mt-1.5 text-[13px] text-secondary">{{ $caption }}</div>
    @endif
</div>
