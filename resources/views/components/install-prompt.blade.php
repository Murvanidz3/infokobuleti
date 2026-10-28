{{-- "Add to home screen" offer on phones (PWA). Android shows the browser prompt; iOS gets the steps. --}}
<div x-data="installPrompt" x-show="open" x-cloak x-transition.opacity
     class="fixed inset-x-3 bottom-3 z-40 rounded-panel border border-line bg-white p-4 shadow-overlay md:hidden" role="dialog" aria-labelledby="install-title">
    <div class="flex items-start gap-3">
        <img src="/icons/icon-192.png" alt="" class="size-11 shrink-0 rounded-tile">
        <div class="min-w-0 flex-1">
            <p id="install-title" class="text-[15px] font-semibold text-navy">{{ __('pwa.install.title') }}</p>
            <p class="mt-0.5 text-[13.5px] leading-normal text-secondary" x-show="!ios">{{ __('pwa.install.text') }}</p>
            <p class="mt-0.5 text-[13.5px] leading-normal text-secondary" x-show="ios">{{ __('pwa.install.ios') }}</p>
        </div>
        <x-icon-button icon="close" variant="ghost" :label="__('pwa.install.later')" x-on:click="dismiss" />
    </div>
    <div class="mt-3 flex gap-2" x-show="!ios">
        <x-button size="sm" class="flex-1" icon="plus" x-on:click="install">{{ __('pwa.install.button') }}</x-button>
        <x-button size="sm" variant="ghost" x-on:click="dismiss">{{ __('pwa.install.later') }}</x-button>
    </div>
</div>
