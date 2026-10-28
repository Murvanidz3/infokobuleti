<x-layout.app :noindex="true" :title="__('auth.login.title')">
    <x-auth.card :title="__('auth.login.title')" :subtitle="__('auth.login.subtitle')">
        <x-auth.login-panel :return-to="session('url.intended')" />
    </x-auth.card>
</x-layout.app>
