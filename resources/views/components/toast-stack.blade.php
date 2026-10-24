{{-- Toasts are pushed with: window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
     The live region is rendered up front so screen readers track it; the Alpine part inside the
     template is mounted by the toasts store on the first toast, keeping it out of page startup. --}}
<div role="status"
     aria-live="polite"
     aria-label="{{ __('components.toast.region') }}"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[70] flex flex-col items-center gap-2.5 p-4 md:items-end md:p-6">
    <template data-toast-stack>
        <div x-data class="contents">
            <template x-for="toast in $store.toasts.items" x-bind:key="toast.id">
                <div x-transition:enter="transition duration-250 ease-soft"
                     x-transition:enter-start="translate-y-3 opacity-0"
                     x-transition:leave="transition duration-200 ease-in"
                     x-transition:leave-end="opacity-0"
                     class="pointer-events-auto flex w-full max-w-[400px] items-start gap-3 rounded-tile bg-navy py-3.5 pr-2 pl-4 text-white shadow-overlay">
                    <span class="mt-0.5 text-coral-hover">
                        <x-icon name="check-circle" :size="20" x-show="toast.type === 'success'" />
                        <x-icon name="alert" :size="20" x-show="toast.type === 'error'" />
                        <x-icon name="info" :size="20" x-show="toast.type !== 'success' && toast.type !== 'error'" />
                    </span>
                    <p class="flex-1 py-0.5 text-sm leading-normal" x-text="toast.message"></p>
                    <button type="button" x-on:click="$store.toasts.dismiss(toast.id)" aria-label="{{ __('components.toast.dismiss') }}" class="-my-1.5 grid size-9 shrink-0 place-items-center rounded-full text-white/75 transition-colors hover:bg-white/8 hover:text-white">
                        <x-icon name="close" :size="18" />
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>
