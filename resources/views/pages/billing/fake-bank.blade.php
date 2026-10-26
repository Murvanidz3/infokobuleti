{{-- The fake test bank's payment page (staging only). Clearly marked as a test; no card data is asked for. --}}
<x-layout.app :title="__('billing.fake_bank.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="min-h-[70vh] bg-surface-alt py-10 md:py-16">
        <div class="container-page">
            <div class="mx-auto max-w-[460px] rounded-panel border border-line bg-white p-6 shadow-card md:p-8">
                <p class="inline-flex items-center gap-1.5 rounded-full bg-coral-tint px-3 py-1 text-[12.5px] font-semibold text-coral-pressed">
                    <x-icon name="alert" :size="14" />{{ __('billing.fake_bank.badge') }}
                </p>
                <h1 class="mt-4 font-serif text-[26px] leading-tight font-semibold text-navy">{{ __('billing.fake_bank.title') }}</h1>
                <p class="mt-2 text-[14.5px] leading-[1.6] text-secondary">{{ __('billing.fake_bank.text') }}</p>

                <dl class="mt-6 flex flex-col gap-3 rounded-tile bg-surface-alt p-4">
                    <div class="flex justify-between gap-3"><dt class="text-[14px] text-secondary">{{ __('billing.fake_bank.purpose') }}</dt><dd class="text-right text-[14px] font-semibold text-navy">{{ $description }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-[14px] text-secondary">{{ __('billing.fake_bank.amount') }}</dt><dd class="text-xl font-bold text-price">{{ $amount }}</dd></div>
                </dl>

                @if ($decided)
                    <p class="mt-6 text-[14.5px] text-secondary">{{ __('billing.fake_bank.done') }}</p>
                @else
                    <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6 flex flex-col gap-2.5">
                        @csrf
                        <x-button type="submit" size="lg" name="approve" value="1" icon="check">{{ __('billing.fake_bank.approve') }}</x-button>
                        <x-button type="submit" size="lg" variant="ghost" name="approve" value="0" icon="close">{{ __('billing.fake_bank.decline') }}</x-button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-layout.app>
