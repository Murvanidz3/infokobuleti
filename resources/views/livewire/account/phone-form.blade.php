<div>
    @if ($step === 'form')
        <form wire:submit="sendCode" class="flex flex-col gap-4" novalidate>
            <x-form.field :label="__('auth.phone.new_label')" name="phone">
                <div @class([
                    'flex min-h-12 items-stretch overflow-hidden rounded-field border bg-white transition-colors duration-200 focus-within:border-navy hover:border-line-strong',
                    'border-coral-pressed' => $errors->has('phone'),
                    'border-line' => ! $errors->has('phone'),
                ])>
                    <span class="flex items-center border-r border-line bg-surface-alt px-3.5 text-[15px] font-semibold text-navy">+995</span>
                    <input id="phone" type="tel" inputmode="numeric" autocomplete="tel-national" placeholder="5XX XX XX XX" maxlength="16"
                           wire:model="phone"
                           class="min-w-0 flex-1 border-0 bg-transparent px-4 text-[16px] text-navy outline-none placeholder:text-secondary/60">
                </div>
            </x-form.field>
            <div class="flex flex-wrap gap-3">
                <x-button type="submit" wire:loading.attr="disabled">{{ __('auth.otp.send') }}</x-button>
                @if ($context === 'settings')
                    <x-button variant="ghost" wire:click="cancel">{{ __('auth.common.cancel') }}</x-button>
                @endif
            </div>
        </form>
    @else
        <form wire:submit="confirm" class="flex flex-col gap-4" novalidate>
            <p class="text-[15px] leading-[1.6] text-secondary">
                {{ __('auth.otp.sent_to', ['length' => config('otp.length')]) }} <strong class="font-semibold whitespace-nowrap text-navy">{{ $pendingFormatted }}</strong>
            </p>

            @if ($devCode)
                <p class="rounded-tile bg-surface-alt px-4 py-3 text-[14px] text-secondary">
                    {{ __('auth.otp.dev_code') }} <strong class="font-semibold tracking-[0.2em] text-navy">{{ $devCode }}</strong>
                </p>
            @endif

            <x-form.field :label="__('auth.otp.code_label')" name="code">
                <input id="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="{{ '\d{'.config('otp.length').'}' }}" maxlength="{{ config('otp.length') }}" placeholder="{{ str_repeat('•', (int) config('otp.length')) }}"
                       wire:model="code" autofocus
                       @class([
                           'block min-h-14 w-full max-w-[280px] rounded-field border bg-white px-4 text-center text-[24px] font-semibold tracking-[0.5em] text-navy transition-colors duration-200 placeholder:text-secondary/40 hover:border-line-strong focus:border-navy',
                           'border-coral-pressed' => $errors->has('code'),
                           'border-line' => ! $errors->has('code'),
                       ])>
            </x-form.field>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit" wire:loading.attr="disabled">{{ __('auth.phone.confirm') }}</x-button>
                <button type="button"
                        wire:key="resend-{{ $resendIn }}-{{ $pendingPhone }}"
                        wire:click="resend"
                        x-data="resendTimer({{ $resendIn }}, @js(__('auth.otp.resend_in')))"
                        x-bind:disabled="left > 0"
                        class="hit-44 relative text-[14px] font-semibold text-navy underline-offset-4 hover:underline disabled:cursor-default disabled:font-medium disabled:text-secondary disabled:no-underline">
                    <span x-show="left > 0" x-text="label"></span>
                    <span x-show="left === 0">{{ __('auth.otp.resend') }}</span>
                </button>
                <x-button variant="ghost" wire:click="cancel" class="ml-auto">{{ __('auth.common.cancel') }}</x-button>
            </div>
        </form>
    @endif
</div>
