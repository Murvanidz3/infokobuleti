<x-account.layout :title="__('account.rooms.title')" :subtitle="$hotel->title">
    <x-slot:actions>
        <x-button variant="secondary" icon="chevron-left" :href="localized_route('account.bookings')">{{ __('account.menu.bookings') }}</x-button>
    </x-slot:actions>

    <livewire:account.hotel-rooms :hotel-id="$hotel->id" />
</x-account.layout>
