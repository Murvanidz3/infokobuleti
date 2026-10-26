<x-layout.app :noindex="true" :title="__('auth.phone.verify_title')">
    <x-auth.card :title="__('auth.phone.verify_title')" :subtitle="__('auth.phone.verify_subtitle')" icon="smartphone">
        <livewire:account.phone-form context="verify" />

        <x-slot:after>
            <a href="{{ session('url.intended', localized_route('home')) }}" class="font-semibold text-navy underline-offset-4 hover:underline">{{ __('auth.phone.skip') }}</a>
        </x-slot:after>
    </x-auth.card>
</x-layout.app>
