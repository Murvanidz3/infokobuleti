{{-- Account billing: wallet, business package, staff invoices, running promotions, history. --}}
<div class="flex flex-col gap-6">
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Wallet --}}
        <section id="top-up" aria-labelledby="wallet-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
            <div class="flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-navy text-white"><x-icon name="wallet" :size="20" /></span>
                <div>
                    <h2 id="wallet-title" class="text-[13.5px] font-medium text-secondary">{{ __('billing.wallet.title') }}</h2>
                    <p class="font-serif text-[28px] leading-tight font-semibold text-navy">{{ $balance }}</p>
                </div>
            </div>
            <p class="mt-3 text-[14px] leading-[1.6] text-secondary">{{ __('billing.wallet.text') }}</p>

            <form wire:submit="topUp" class="mt-4 flex flex-col gap-3">
                <x-account.choice-pills model="amount" :options="$presets" :label="__('billing.wallet.amount')" />
                <x-form.field :label="__('billing.wallet.custom')" name="amount" :hint="__('billing.wallet.limits', $limits)">
                    <x-form.input name="amount" type="number" inputmode="decimal" step="0.01" :min="$limits['min']" :max="$limits['max']" wire:model.blur="amount" icon="wallet" />
                </x-form.field>
                @if (count($gateways) > 1)
                    <x-account.choice-pills model="gateway" :options="$gateways" :label="__('billing.checkout.bank')" />
                @endif
                <x-button type="submit" icon="plus" wire:loading.attr="disabled" wire:target="topUp" :disabled="$gateways === []">{{ __('billing.wallet.top_up') }}</x-button>
            </form>
        </section>

        {{-- Business package --}}
        <section aria-labelledby="package-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
            <div class="flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-coral-tint text-coral-pressed"><x-icon name="briefcase" :size="20" /></span>
                <div class="min-w-0">
                    <h2 id="package-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('billing.package.title') }}</h2>
                    @if ($subscription)
                        <p class="text-[13.5px] font-semibold text-navy">{{ __('billing.package.active', ['date' => $subscription['until']]) }}</p>
                    @endif
                </div>
            </div>

            <ul class="mt-3 flex flex-col gap-1.5 text-[14px] text-secondary">
                @foreach (['limit', 'badge', 'logo', 'analytics'] as $perk)
                    <li class="flex items-start gap-2"><x-icon name="check" :size="16" class="mt-0.5 shrink-0 text-navy" />{{ __('billing.package.perks.'.$perk) }}</li>
                @endforeach
            </ul>

            @if ($subscription)
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-button size="sm" variant="secondary" icon="chart" :href="localized_route('account.analytics')">{{ __('billing.analytics.open') }}</x-button>
                </div>
            @endif

            @if ($packages !== [])
                <div role="radiogroup" aria-labelledby="package-title" class="mt-4 flex flex-col gap-2">
                    @foreach ($packages as $plan)
                        <button type="button" role="radio" wire:key="package-{{ $plan['id'] }}" wire:click="$set('package', {{ $plan['id'] }})"
                                aria-checked="{{ $plan['id'] === $this->package ? 'true' : 'false' }}"
                                class="flex min-h-14 items-center justify-between gap-3 rounded-tile border border-line px-4 py-2.5 text-left transition-colors duration-200 hover:border-line-strong aria-checked:border-navy aria-checked:bg-surface-alt">
                            <span>
                                <span class="block text-[14.5px] font-semibold text-navy">{{ $plan['name'] }}</span>
                                <span class="block text-[12.5px] text-secondary">{{ $plan['days'] }}@if ($plan['limit']) · {{ __('billing.package.limit', ['count' => $plan['limit']]) }}@endif</span>
                            </span>
                            <span class="text-[17px] font-bold text-price">{{ $plan['price'] }}</span>
                        </button>
                    @endforeach
                </div>

                @include('livewire.billing.partials.payment-method', ['balance' => $balance, 'enoughBalance' => $enoughBalance, 'gateways' => $gateways])

                <x-button class="mt-4 w-full" icon="card" wire:click="buyPackage" wire:loading.attr="disabled" wire:target="buyPackage">
                    {{ $subscription ? __('billing.package.extend') : __('billing.package.buy') }}
                </x-button>
            @else
                <p class="mt-4 text-[14px] text-secondary">{{ __('billing.package.none') }}</p>
            @endif
        </section>
    </div>

    {{-- Invoices issued by staff --}}
    @if ($invoices !== [])
        <section aria-labelledby="invoices-title" class="rounded-panel border border-coral bg-white p-5 shadow-card md:p-6">
            <h2 id="invoices-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('billing.invoices.title') }}</h2>
            <ul class="mt-3 flex flex-col divide-y divide-line">
                @foreach ($invoices as $invoice)
                    <li wire:key="invoice-{{ $invoice['id'] }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[15px] font-semibold text-navy">{{ $invoice['title'] }}</p>
                            <p class="text-[13px] text-secondary">{{ $invoice['date'] }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[17px] font-bold text-price">{{ $invoice['amount'] }}</span>
                            <x-button size="sm" icon="card" wire:click="payInvoice({{ $invoice['id'] }}, 'card')" wire:loading.attr="disabled">{{ __('billing.checkout.card') }}</x-button>
                            @if ($invoice['payable'])
                                <x-button size="sm" variant="secondary" icon="wallet" wire:click="payInvoice({{ $invoice['id'] }}, 'balance')" wire:loading.attr="disabled">{{ __('billing.checkout.balance') }}</x-button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Running promotions --}}
    <section aria-labelledby="promotions-title">
        <h2 id="promotions-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('billing.promotions.title') }}</h2>
        @if ($promotions === [])
            <p class="mt-2 text-[14.5px] text-secondary">{{ __('billing.promotions.empty') }} <a href="{{ localized_route('account.listings', ['group' => 'active']) }}" class="font-semibold text-navy underline">{{ __('billing.promotions.choose') }}</a></p>
        @else
            <ul class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($promotions as $promotion)
                    <li class="flex min-w-0 items-center gap-3 rounded-card border border-line bg-white p-4 shadow-card">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-coral-tint text-coral-pressed"><x-icon name="star" :size="18" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12.5px] font-semibold text-coral-pressed">{{ $promotion['label'] }} · <span class="font-medium text-secondary">{{ $promotion['until'] }}</span></p>
                            <p class="truncate text-[14.5px] font-semibold text-navy">
                                @if ($promotion['url'])<a href="{{ $promotion['url'] }}" class="hover:text-coral-pressed">{{ $promotion['title'] }}</a>@else{{ $promotion['title'] }}@endif
                            </p>
                        </div>
                        <x-button size="sm" variant="ghost" icon="arrow-up" class="shrink-0" :href="$promotion['promote']" :aria-label="__('billing.promotions.extend')"><span class="max-xs:sr-only">{{ __('billing.promotions.extend') }}</span></x-button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Order history --}}
    <section aria-labelledby="history-title">
        <h2 id="history-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('billing.history.title') }}</h2>
        @if ($rows === [])
            <x-empty-state class="mt-3" icon="file" :title="__('account.billing.empty_title')" :text="__('billing.history.empty')" />
        @else
            <ul class="mt-3 flex flex-col gap-2.5">
                @foreach ($rows as $row)
                    <li wire:key="order-{{ $row['id'] }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-card border border-line bg-white px-4 py-3.5 shadow-card md:px-5">
                        <div class="min-w-0 flex-1 basis-[220px]">
                            <p class="truncate text-[15px] font-semibold text-navy">{{ $row['title'] }}</p>
                            <p class="truncate text-[13px] text-secondary">{{ $row['date'] }}@if ($row['number']) · {{ $row['number'] }}@endif @if ($row['method']) · {{ $row['method'] }}@endif @if ($row['subject']) · {{ $row['subject'] }}@endif</p>
                        </div>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-[3px] text-xs font-semibold',
                            'bg-navy text-white' => $row['tone'] === 'success',
                            'bg-coral-tint text-coral-pressed' => in_array($row['tone'], ['warning', 'danger'], true),
                            'bg-line text-navy' => $row['tone'] === 'gray',
                        ])>{{ $row['status'] }}</span>
                        <span class="text-right">
                            <span class="block text-[16px] font-bold text-navy">{{ $row['amount'] }}</span>
                            @if ($row['refunded'])<span class="block text-[12.5px] text-secondary">{{ __('billing.history.refunded', ['amount' => $row['refunded']]) }}</span>@endif
                        </span>
                        @if ($row['invoice'])
                            <x-button size="sm" variant="ghost" icon="download" :href="$row['invoice']" target="_blank">{{ __('billing.history.invoice') }}</x-button>
                        @endif
                    </li>
                @endforeach
            </ul>
            <x-pagination :paginator="$orders" class="mt-4" />
        @endif
    </section>

    @if ($ledger !== [])
        <section aria-labelledby="ledger-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
            <h2 id="ledger-title" class="font-serif text-[18px] font-semibold text-navy">{{ __('billing.wallet.ledger') }}</h2>
            <ul class="mt-2 divide-y divide-line">
                @foreach ($ledger as $entry)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-[14px]">
                        <span class="min-w-0">
                            <span class="font-semibold text-navy">{{ $entry['type'] }}</span>
                            <span class="text-secondary"> · {{ $entry['date'] }}@if ($entry['note']) · {{ $entry['note'] }}@endif</span>
                        </span>
                        <span @class(['shrink-0 font-semibold', 'text-navy' => $entry['credit'], 'text-secondary' => ! $entry['credit']])>{{ $entry['amount'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
