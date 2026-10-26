<x-account.layout :title="__('account.dashboard.title', ['name' => auth()->user()->name])" :subtitle="__('account.dashboard.subtitle')">
    <x-slot:actions>
        <x-button :href="localized_route('listings.create')" icon="plus">{{ __('nav.add_listing') }}</x-button>
    </x-slot:actions>

    <livewire:account.dashboard />
</x-account.layout>
