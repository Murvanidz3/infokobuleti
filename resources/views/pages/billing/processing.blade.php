{{-- Back from the bank, payment not confirmed yet: re-checks every few seconds. --}}
<x-account.layout :title="__('billing.return.processing_title')" :subtitle="__('billing.return.processing_text')">
    <x-slot:head>
        <meta http-equiv="refresh" content="4;url={{ $retry }}">
    </x-slot:head>
    <div class="flex items-center gap-4 rounded-panel border border-line bg-white p-6 shadow-card">
        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-surface-alt text-navy motion-safe:animate-spin"><x-icon name="refresh" :size="22" /></span>
        <div>
            <p class="text-[15px] font-semibold text-navy">{{ __('billing.return.waiting') }}</p>
            <a href="{{ $retry }}" class="text-[14px] text-secondary underline">{{ __('billing.return.check_again') }}</a>
        </div>
    </div>
</x-account.layout>
