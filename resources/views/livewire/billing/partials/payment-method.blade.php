{{-- Payment method (balance or card) and, when several banks are on, the bank. Binds to PaysOrders. --}}
<fieldset class="mt-4">
    <legend class="text-[13.5px] font-semibold text-navy">{{ __('billing.checkout.method') }}</legend>
    <div class="mt-2 flex flex-col gap-2">
        <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-tile border border-line px-4 transition-colors duration-200 has-checked:border-navy has-checked:bg-surface-alt">
            <input type="radio" value="card" wire:model.live="method" class="size-4 accent-navy">
            <x-icon name="card" :size="18" class="text-navy" />
            <span class="text-[14.5px] font-medium text-navy">{{ __('billing.checkout.card') }}</span>
        </label>
        <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-tile border border-line px-4 transition-colors duration-200 has-checked:border-navy has-checked:bg-surface-alt">
            <input type="radio" value="balance" wire:model.live="method" class="size-4 accent-navy">
            <x-icon name="wallet" :size="18" class="text-navy" />
            <span class="flex-1 text-[14.5px] font-medium text-navy">{{ __('billing.checkout.balance') }}</span>
            <span class="text-[13.5px] font-semibold text-secondary">{{ $balance }}</span>
        </label>
    </div>

    @if ($this->method === 'balance' && ! $enoughBalance)
        <p class="mt-2 flex items-center gap-1.5 text-[13px] font-medium text-coral-pressed">
            <x-icon name="alert" :size="15" />{{ __('billing.errors.insufficient_balance') }}
            <a href="{{ localized_route('account.billing') }}#top-up" class="underline">{{ __('billing.wallet.top_up') }}</a>
        </p>
    @endif

    @if ($this->method === 'card' && count($gateways) > 1)
        <div class="mt-3">
            <x-account.choice-pills model="gateway" :options="$gateways" :label="__('billing.checkout.bank')" />
        </div>
    @endif
</fieldset>
