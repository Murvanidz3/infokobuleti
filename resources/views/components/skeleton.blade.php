@props([
    'variant' => 'line',
    'lines' => 3,
])

@php
    $bone = 'rounded-full bg-line motion-safe:animate-pulse';
@endphp

<div aria-hidden="true" {{ $attributes->class(['w-full']) }}>
    @switch($variant)
        @case('card')
            <div class="overflow-hidden rounded-card border border-line bg-white">
                <div class="aspect-4/3 bg-line motion-safe:animate-pulse"></div>
                <div class="flex flex-col gap-3 p-[18px]">
                    <div class="{{ $bone }} h-4 w-3/4"></div>
                    <div class="{{ $bone }} h-3 w-1/2"></div>
                    <div class="mt-3 flex justify-between border-t border-line pt-4">
                        <div class="{{ $bone }} h-5 w-1/4"></div>
                        <div class="{{ $bone }} h-3 w-1/5"></div>
                    </div>
                </div>
            </div>
            @break

        @case('list-item')
            <div class="flex items-center gap-3.5 px-[18px] py-4">
                <div class="size-11 shrink-0 rounded-[10px] bg-line motion-safe:animate-pulse"></div>
                <div class="flex flex-1 flex-col gap-2">
                    <div class="{{ $bone }} h-3.5 w-2/3"></div>
                    <div class="{{ $bone }} h-3 w-2/5"></div>
                </div>
                <div class="{{ $bone }} h-4 w-14"></div>
            </div>
            @break

        @case('block')
            <div class="h-full min-h-24 rounded-card bg-line motion-safe:animate-pulse"></div>
            @break

        @default
            <div class="flex flex-col gap-2.5">
                @for ($i = 0; $i < $lines; $i++)
                    <div @class([$bone, 'h-3', 'w-3/5' => $i === $lines - 1 && $lines > 1, 'w-full' => $i !== $lines - 1 || $lines === 1])></div>
                @endfor
            </div>
    @endswitch
</div>
