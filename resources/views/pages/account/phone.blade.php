<x-layout.app :title="__('auth.phone.verify_title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <x-auth.card :title="__('auth.phone.verify_title')" :subtitle="__('auth.phone.verify_subtitle')" icon="smartphone">
        <livewire:account.phone-form context="verify" />

        <x-slot:after>
            <a href="{{ session('url.intended', localized_route('home')) }}" class="font-semibold text-navy underline-offset-4 hover:underline">{{ __('auth.phone.skip') }}</a>
        </x-slot:after>
    </x-auth.card>
</x-layout.app>
