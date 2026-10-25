<x-layout.app :title="__('auth.login.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <x-auth.card :title="__('auth.login.title')" :subtitle="__('auth.login.subtitle')">
        <x-auth.login-panel :return-to="session('url.intended')" />
    </x-auth.card>
</x-layout.app>
