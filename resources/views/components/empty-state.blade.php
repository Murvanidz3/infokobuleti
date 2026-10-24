@props([
    'icon' => 'inbox',
    'title' => null,
    'text' => null,
])

<div {{ $attributes->class(['flex flex-col items-center gap-3 rounded-card border border-line bg-white px-6 py-14 text-center']) }}>
    <span class="mb-1 grid size-16 place-items-center rounded-full bg-surface-alt text-secondary">
        <x-icon :name="$icon" :size="28" />
    </span>
    <h3 class="font-serif text-xl font-semibold text-navy">{{ $title ?? __('components.empty.title') }}</h3>
    <p class="max-w-[420px] text-[15px] leading-[1.6] text-secondary">{{ $text ?? __('components.empty.text') }}</p>
    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>
