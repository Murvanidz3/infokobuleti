{{-- Alert channels besides email: Telegram link and web push on this device. --}}
<div class="flex flex-col gap-4">
    @if ($telegram)
        <section aria-labelledby="telegram-title" class="rounded-panel border border-line bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="telegram" :size="20" /></span>
                <div class="min-w-0">
                    <h2 id="telegram-title" class="font-serif text-[18px] font-semibold text-navy">Telegram</h2>
                    <p class="text-[13px] text-secondary">{{ $linked ? __('alerts.telegram.linked') : __('alerts.telegram.not_linked') }}</p>
                </div>
            </div>
            <p class="mt-3 text-[13.5px] leading-normal text-secondary">{{ __('alerts.telegram.text') }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @if ($linked)
                    <x-button size="sm" variant="ghost" icon="close" wire:click="unlinkTelegram" wire:confirm="{{ __('alerts.telegram.confirm_unlink') }}">{{ __('alerts.telegram.unlink') }}</x-button>
                @elseif ($telegramLink)
                    <x-button size="sm" icon="external" :href="$telegramLink" target="_blank" rel="noopener">{{ __('alerts.telegram.open') }}</x-button>
                    <p class="w-full text-[12.5px] text-secondary">{{ __('alerts.telegram.open_hint') }}</p>
                @else
                    <x-button size="sm" icon="telegram" wire:click="linkTelegram" wire:loading.attr="disabled">{{ __('alerts.telegram.link') }}</x-button>
                @endif
            </div>
        </section>
    @endif

    @if ($push)
        <section aria-labelledby="push-title" class="rounded-panel border border-line bg-white p-5 shadow-card"
                 x-data="pushToggle(@js($vapidKey), @js(localized_route('account.push.store')))">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="smartphone" :size="20" /></span>
                <div class="min-w-0">
                    <h2 id="push-title" class="font-serif text-[18px] font-semibold text-navy">{{ __('alerts.push.title_settings') }}</h2>
                    <p class="text-[13px] text-secondary">{{ trans_choice('alerts.push.devices', $devices, ['count' => $devices]) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[13.5px] leading-normal text-secondary" x-show="supported">{{ __('alerts.push.text') }}</p>
            <p class="mt-3 text-[13.5px] leading-normal text-secondary" x-show="!supported" x-cloak>{{ __('alerts.push.unsupported') }}</p>
            <div class="mt-4 flex flex-wrap gap-2" x-show="supported">
                <x-button size="sm" icon="check" x-show="!subscribed" x-on:click="enable" x-bind:disabled="busy">{{ __('alerts.push.enable') }}</x-button>
                <x-button size="sm" variant="ghost" icon="close" x-show="subscribed" x-cloak x-on:click="disable" x-bind:disabled="busy">{{ __('alerts.push.disable') }}</x-button>
            </div>
            <p class="mt-2 text-[12.5px] font-medium text-coral-pressed" x-show="denied" x-cloak>{{ __('alerts.push.denied') }}</p>
        </section>
    @endif
</div>
