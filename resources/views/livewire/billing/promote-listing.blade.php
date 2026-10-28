{{-- Promote one listing: pick a product and duration, then pay from the balance or by card. --}}
<div class="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start lg:gap-6">
    <div class="flex flex-col gap-4">
        @unless ($canPromote)
            <p class="flex items-start gap-2.5 rounded-tile bg-coral-tint px-4 py-3 text-[14px] leading-normal text-navy">
                <x-icon name="alert" :size="18" class="mt-px shrink-0" />{{ __('billing.promote.not_live') }}
            </p>
        @endunless

        @forelse ($groups as $group)
            <section wire:key="group-{{ $group['product'] }}" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6" aria-labelledby="group-{{ $group['product'] }}">
                <div class="flex items-start gap-3">
                    <span @class([
                        'grid size-11 shrink-0 place-items-center rounded-full',
                        'bg-navy text-white' => $group['product'] === 'super_vip',
                        'bg-coral-tint text-navy' => $group['product'] === 'vip',
                        'bg-surface-alt text-navy' => ! in_array($group['product'], ['super_vip', 'vip'], true),
                    ])>
                        <x-icon :name="match ($group['product']) { 'super_vip' => 'crown', 'vip' => 'star', 'featured_hotel' => 'house', 'auto_bump' => 'refresh', default => 'arrow-up' }" :size="20" />
                    </span>
                    <div class="min-w-0">
                        <h2 id="group-{{ $group['product'] }}" class="font-serif text-[20px] font-semibold text-navy">{{ $group['label'] }}</h2>
                        <p class="mt-1 text-[14px] leading-[1.6] text-secondary">{{ $group['text'] }}</p>
                    </div>
                </div>

                <div role="radiogroup" aria-labelledby="group-{{ $group['product'] }}" class="mt-4 grid grid-cols-2 gap-2.5 md:grid-cols-4">
                    @foreach ($group['plans'] as $plan)
                        <button type="button" role="radio" wire:key="plan-{{ $plan['id'] }}" wire:click="choose({{ $plan['id'] }})"
                                aria-checked="{{ $plan['id'] === $this->plan ? 'true' : 'false' }}"
                                @disabled(! $canPromote)
                                class="flex min-h-[76px] flex-col items-start justify-center gap-1 rounded-tile border border-line bg-white px-4 py-3 text-left transition-colors duration-200 hover:border-line-strong disabled:opacity-50 aria-checked:border-navy aria-checked:bg-navy aria-checked:text-white">
                            <span class="text-[13.5px] font-semibold">{{ $plan['days'] }}</span>
                            <span class="text-[17px] font-bold">{{ $plan['price'] }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @empty
            <x-empty-state icon="star" :title="__('billing.promote.no_plans_title')" :text="__('billing.promote.no_plans_text')" />
        @endforelse
    </div>

    <aside class="mt-6 flex flex-col gap-4 lg:sticky lg:top-[92px] lg:mt-0">
        <div class="rounded-panel border border-line bg-white p-5 shadow-card">
            <div class="flex gap-3">
                <span class="relative block size-16 shrink-0 overflow-hidden rounded-tile bg-surface-alt">
                    @if ($row['image'])
                        <img src="{{ $row['image'] }}" alt="" class="size-full object-cover">
                    @else
                        <span class="grid size-full place-items-center text-line-strong"><x-icon name="image" :size="22" /></span>
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="text-[12.5px] font-medium text-secondary">{{ $row['module'] }} · {{ $row['status'] }}</p>
                    <p class="mt-0.5 line-clamp-2 text-[15px] leading-snug font-semibold text-navy">{{ $row['title'] ?: __('account.listings.untitled') }}</p>
                </div>
            </div>

            @if ($active !== [])
                <ul class="mt-4 flex flex-col gap-2 border-t border-line pt-4">
                    @foreach ($active as $promotion)
                        <li class="flex items-center gap-2 text-[13.5px] text-navy">
                            <x-icon :name="$promotion['icon']" :size="16" class="text-coral-pressed" />
                            <span class="font-semibold">{{ $promotion['label'] }}</span>
                            <span class="text-secondary">{{ $promotion['until'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-panel border border-line bg-white p-5 shadow-card">
            <h2 class="font-serif text-[18px] font-semibold text-navy">{{ __('billing.checkout.title') }}</h2>

            @if ($selected)
                <dl class="mt-3 flex items-baseline justify-between gap-3 border-b border-line pb-3">
                    <dt class="text-[14px] text-secondary">{{ $selected->name }}</dt>
                    <dd class="text-xl font-bold text-price">{{ $selectedPrice }}</dd>
                </dl>
            @endif

            @include('livewire.billing.partials.payment-method', ['balance' => $balance, 'enoughBalance' => $enoughBalance, 'gateways' => $gateways])

            <x-button class="mt-4 w-full" size="lg" icon="card" wire:click="buy" wire:loading.attr="disabled" wire:target="buy" :disabled="! $canPromote || ! $selected">
                {{ __('billing.checkout.pay') }}
            </x-button>
            <p class="mt-3 text-center text-[12.5px] leading-normal text-secondary">{{ __('billing.checkout.secure') }}</p>
        </div>
    </aside>
</div>
