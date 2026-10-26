<x-layout.app :title="__('pwa.offline.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>
    <section class="bg-surface-alt py-16 md:py-24">
        <div class="container-page">
            <x-empty-state icon="wifi" :title="__('pwa.offline.title')" :text="__('pwa.offline.text')">
                <x-slot:action>
                    <x-button icon="refresh" onclick="window.location.reload()">{{ __('pwa.offline.retry') }}</x-button>
                </x-slot:action>
            </x-empty-state>
        </div>
    </section>
</x-layout.app>
