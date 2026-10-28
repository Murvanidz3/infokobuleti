<x-account.layout :title="__('account.listings.title')" :subtitle="__('account.listings.subtitle')">
    <x-slot:actions>
        <x-button :href="localized_route('listings.create')" icon="plus">{{ __('nav.add_listing') }}</x-button>
    </x-slot:actions>

    <livewire:account.my-listings />
</x-account.layout>
