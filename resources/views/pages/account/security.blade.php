<x-layout.app :title="__('auth.security.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="bg-surface-alt py-8 md:py-14">
        <div class="container-page">
            <div class="mx-auto w-full max-w-[760px]">
                <h1 class="font-serif text-[28px] leading-tight font-semibold text-navy md:text-[34px]">{{ __('auth.security.title') }}</h1>
                <p class="mt-2 text-[15px] leading-[1.6] text-secondary">{{ __('auth.security.subtitle') }}</p>

                <x-auth.flash class="mt-6" />

                <livewire:account.security />
            </div>
        </div>
    </section>
</x-layout.app>
