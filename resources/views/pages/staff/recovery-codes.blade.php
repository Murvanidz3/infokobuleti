<x-layout.app :noindex="true" :title="__('auth.staff.recovery_title')">
    <x-auth.card :title="__('auth.staff.recovery_title')" :subtitle="__('auth.staff.recovery_subtitle')" icon="key">
        <x-auth.recovery-codes :codes="$codes" />

        <x-button :href="localized_route('account.security')" size="lg" class="mt-6 w-full" icon-right="arrow-right">{{ __('auth.staff.recovery_saved') }}</x-button>
    </x-auth.card>
</x-layout.app>
