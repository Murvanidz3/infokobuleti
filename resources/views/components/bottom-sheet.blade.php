@props([
    'name',
    'title',
])

{{-- Slides up from the bottom on mobile; behaves as a centred modal from 720px. Opened like <x-modal>. --}}
<dialog x-data="dialog(@js($name))"
        x-on:click="closeOnBackdrop($event)"
        aria-labelledby="dialog-{{ $name }}-title"
        {{ $attributes->class([
            'overflow-hidden bg-white p-0 text-navy shadow-overlay backdrop:bg-navy/55',
            'max-md:mx-0 max-md:mt-auto max-md:mb-0 max-md:max-h-[85dvh] max-md:w-full max-md:max-w-none max-md:rounded-t-panel',
            'md:m-auto md:max-h-[calc(100dvh-32px)] md:w-[calc(100%-32px)] md:max-w-[560px] md:rounded-panel',
            'transition-[opacity,translate,display,overlay] transition-discrete duration-300 ease-soft',
            'max-md:translate-y-full max-md:open:translate-y-0 max-md:starting:open:translate-y-full',
            'md:translate-y-4 md:opacity-0 md:open:translate-y-0 md:open:opacity-100 md:starting:open:translate-y-4 md:starting:open:opacity-0',
        ]) }}>
    <div class="flex max-h-[inherit] flex-col">
        <div class="flex justify-center pt-2.5 md:hidden" aria-hidden="true">
            <span class="h-1 w-10 rounded-full bg-line-strong"></span>
        </div>
        <header class="flex items-center justify-between gap-4 py-3 pr-4 pl-6">
            <h2 id="dialog-{{ $name }}-title" class="font-serif text-xl font-semibold">{{ $title }}</h2>
            <x-icon-button icon="close" :label="__('components.dialog.close')" variant="ghost" x-on:click="$root.close()" />
        </header>
        <div class="overflow-y-auto px-6 pb-6 text-[15px] leading-[1.6] text-secondary">
            {{ $slot }}
        </div>
        @isset($footer)
            <footer class="flex gap-3 border-t border-line px-6 py-4 pb-[max(16px,env(safe-area-inset-bottom))]">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</dialog>
