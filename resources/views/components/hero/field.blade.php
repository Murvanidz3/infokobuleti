@props(['icon', 'label', 'for' => null])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->class(['flex min-h-16 min-w-0 cursor-pointer items-center gap-3 rounded-field bg-surface-alt px-4 py-3 transition-colors duration-200 focus-within:outline-2 focus-within:outline-coral hover:bg-line']) }}>
    <x-icon :name="$icon" :size="20" class="text-navy" />
    <span class="min-w-0 flex-1">
        <span class="mb-[3px] block text-xs font-medium text-secondary">{{ $label }}</span>
        {{ $slot }}
    </span>
</label>
