<x-layout.app :noindex="true" :title="__('auth.onboarding.title')">
    <x-auth.card :title="__('auth.onboarding.title')" :subtitle="__('auth.onboarding.subtitle')" icon="user">
        <livewire:onboarding />
    </x-auth.card>
</x-layout.app>
