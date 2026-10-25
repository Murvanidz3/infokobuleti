<x-layout.app :title="__('auth.staff.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <x-auth.card :title="__('auth.staff.title')" :subtitle="__('auth.staff.subtitle')" icon="shield">
        <form method="POST" action="{{ localized_route('staff.login.store') }}" class="flex flex-col gap-5">
            @csrf
            <x-form.field :label="__('auth.staff.email')" name="email">
                <x-form.input name="email" type="email" icon="mail" autocomplete="username" required autofocus />
            </x-form.field>

            <x-form.field :label="__('auth.staff.password')" name="password">
                <x-form.input name="password" type="password" icon="key" autocomplete="current-password" required />
            </x-form.field>

            <x-form.checkbox name="remember">{{ __('auth.login.remember') }}</x-form.checkbox>

            <x-button type="submit" size="lg" class="w-full">{{ __('auth.staff.continue') }}</x-button>
        </form>
    </x-auth.card>
</x-layout.app>
