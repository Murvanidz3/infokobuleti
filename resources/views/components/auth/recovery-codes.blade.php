@props(['codes'])

<div x-data="{ copied: false }" {{ $attributes->class(['flex flex-col gap-3']) }}>
    <ul class="grid grid-cols-2 gap-2 rounded-tile border border-line bg-surface-alt p-4 font-mono text-[15px] tracking-[0.08em] text-navy">
        @foreach ($codes as $code)
            <li class="py-1 text-center">{{ $code }}</li>
        @endforeach
    </ul>
    <button type="button"
            x-on:click="navigator.clipboard.writeText(@js(implode("\n", $codes))).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            class="hit-44 relative inline-flex items-center gap-2 self-start text-[14px] font-semibold text-navy underline-offset-4 hover:underline">
        <x-icon name="copy" :size="16" />
        <span x-show="!copied">{{ __('auth.staff.recovery_copy') }}</span>
        <span x-show="copied" x-cloak>{{ __('auth.staff.recovery_copied') }}</span>
    </button>
</div>
