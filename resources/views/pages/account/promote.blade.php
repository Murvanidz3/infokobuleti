<x-account.layout :title="__('billing.promote.title')" :subtitle="__('billing.promote.subtitle')">
    <x-slot:actions>
        <x-button variant="ghost" icon="chevron-left" :href="localized_route('account.listings')">{{ __('billing.promote.back') }}</x-button>
    </x-slot:actions>
    <livewire:billing.promote-listing :type="$type" :id="$id" />
</x-account.layout>
