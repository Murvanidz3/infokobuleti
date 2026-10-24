@props([
    'name',
    'title',
    'size' => 'md',
])

{{-- Open with $dispatch('open-dialog', 'name'); close with Escape, the close button, a backdrop click or $dispatch('close-dialog', 'name'). --}}
<dialog x-data="dialog(@js($name))"
        x-on:click="closeOnBackdrop($event)"
        aria-labelledby="dialog-{{ $name }}-title"
        {{ $attributes->class([
            'm-auto max-h-[calc(100dvh-32px)] w-[calc(100%-32px)] overflow-hidden rounded-panel bg-white p-0 text-navy shadow-overlay',
            'translate-y-4 opacity-0 transition-[opacity,translate,display,overlay] transition-discrete duration-300 ease-soft open:translate-y-0 open:opacity-100 starting:open:translate-y-4 starting:open:opacity-0',
            'backdrop:bg-navy/55 backdrop:backdrop-blur-[2px]',
            'max-w-[440px]' => $size === 'sm',
            'max-w-[560px]' => $size === 'md',
            'max-w-[760px]' => $size === 'lg',
        ]) }}>
    <div class="flex max-h-[calc(100dvh-32px)] flex-col">
        <header class="flex items-center justify-between gap-4 border-b border-line py-4 pr-4 pl-6">
            <h2 id="dialog-{{ $name }}-title" class="font-serif text-[22px] font-semibold">{{ $title }}</h2>
            <x-icon-button icon="close" :label="__('components.dialog.close')" variant="ghost" x-on:click="$root.close()" />
        </header>
        <div class="overflow-y-auto px-6 py-5 text-[15px] leading-[1.6] text-secondary">
            {{ $slot }}
        </div>
        @isset($footer)
            <footer class="flex flex-wrap justify-end gap-3 border-t border-line px-6 py-4">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</dialog>
