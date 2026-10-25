<x-layout.app :title="__('auth.onboarding.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <x-auth.card :title="__('auth.onboarding.title')" :subtitle="__('auth.onboarding.subtitle')" icon="user">
        <livewire:onboarding />
    </x-auth.card>
</x-layout.app>
