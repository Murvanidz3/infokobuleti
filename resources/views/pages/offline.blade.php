<x-layout.app :noindex="true" :title="__('pwa.offline.title')">
    <section class="bg-surface-alt py-16 md:py-24">
        <div class="container-page">
            <x-empty-state icon="wifi" :title="__('pwa.offline.title')" :text="__('pwa.offline.text')">
                <x-slot:action>
                    <x-button icon="refresh" x-data x-on:click="window.location.reload()">{{ __('pwa.offline.retry') }}</x-button>
                </x-slot:action>
            </x-empty-state>
        </div>
    </section>
</x-layout.app>
