<div class="mt-8 flex flex-col gap-6">
    {{-- Sign-in methods --}}
    <section class="rounded-panel border border-line bg-white p-5 shadow-card md:p-7" aria-labelledby="methods-title">
        <h2 id="methods-title" class="font-serif text-[21px] font-semibold text-navy">{{ __('auth.methods.title') }}</h2>
        <p class="mt-1.5 text-[14px] text-secondary">{{ __('auth.methods.subtitle') }}</p>

        <ul class="mt-5 divide-y divide-line">
            {{-- Phone --}}
            <li class="py-4 first:pt-0">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="smartphone" :size="20" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-semibold text-navy">{{ __('auth.methods.phone') }}</p>
                        <p class="text-[14px] text-secondary">
                            @if ($user->hasVerifiedPhone())
                                {{ $phone }} · <span class="text-navy">{{ __('auth.methods.verified') }}</span>
                            @else
                                {{ __('auth.methods.phone_missing') }}
                            @endif
                        </p>
                    </div>
                    @unless ($editingPhone || $user->isStaff())
                        <div class="flex gap-2">
                            <x-button variant="secondary" size="sm" wire:click="$set('editingPhone', true)">
                                {{ $user->hasVerifiedPhone() ? __('auth.methods.change') : __('auth.methods.add') }}
                            </x-button>
                            @if ($user->canRemoveSignInMethod('phone'))
                                <x-button variant="ghost" size="sm" wire:click="removePhone" wire:confirm="{{ __('auth.methods.confirm_remove_phone') }}">{{ __('auth.methods.remove') }}</x-button>
                            @endif
                        </div>
                    @endunless
                </div>

                @if ($editingPhone)
                    <div class="mt-5 rounded-tile bg-surface-alt p-4 md:p-5">
                        <livewire:account.phone-form context="settings" :key="'phone-form-'.$user->id" />
                    </div>
                @endif
            </li>

            {{-- Google (not for staff, who sign in with password + authenticator) --}}
            @unless ($user->isStaff())
                <li class="flex flex-wrap items-center gap-4 py-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt"><x-auth.google-logo :size="20" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-semibold text-navy">Google</p>
                        <p class="text-[14px] text-secondary">{{ $user->hasGoogle() ? __('auth.methods.connected') : __('auth.methods.not_connected') }}</p>
                    </div>
                    @if ($user->hasGoogle())
                        @if ($user->canRemoveSignInMethod('google'))
                            <x-button variant="ghost" size="sm" wire:click="unlinkGoogle" wire:confirm="{{ __('auth.methods.confirm_unlink_google') }}">{{ __('auth.methods.disconnect') }}</x-button>
                        @else
                            <span class="text-[13px] text-secondary">{{ __('auth.methods.last_method') }}</span>
                        @endif
                    @else
                        <x-button variant="secondary" size="sm" :href="localized_route('auth.google', ['intent' => 'link'])">{{ __('auth.methods.connect') }}</x-button>
                    @endif
                </li>
            @endunless

            {{-- Staff: password + two-factor --}}
            @if ($user->isStaff())
                <li class="flex flex-wrap items-center gap-4 py-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="key" :size="20" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-semibold text-navy">{{ __('auth.methods.password') }}</p>
                        <p class="text-[14px] text-secondary">{{ $user->email }}</p>
                    </div>
                </li>
                <li class="py-4 last:pb-0">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="shield" :size="20" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[15px] font-semibold text-navy">{{ __('auth.methods.two_factor') }}</p>
                            <p class="text-[14px] text-secondary">
                                @if ($user->hasTwoFactorEnabled())
                                    {{ __('auth.methods.two_factor_on', ['date' => app(\App\Services\DateFormatter::class)->date($user->two_factor_confirmed_at)]) }}
                                    · {{ trans_choice('auth.methods.recovery_left', $recoveryCodesLeft, ['count' => $recoveryCodesLeft]) }}
                                @else
                                    {{ __('auth.methods.two_factor_off') }}
                                @endif
                            </p>
                        </div>
                        @if ($user->hasTwoFactorEnabled())
                            <x-button variant="secondary" size="sm" wire:click="regenerateRecoveryCodes" wire:confirm="{{ __('auth.methods.confirm_regenerate') }}">{{ __('auth.methods.regenerate') }}</x-button>
                        @endif
                    </div>

                    @if ($recoveryCodes !== [])
                        <div class="mt-5 rounded-tile border border-line p-4 md:p-5">
                            <p class="mb-3 text-[14px] text-secondary">{{ __('auth.staff.recovery_subtitle') }}</p>
                            <x-auth.recovery-codes :codes="$recoveryCodes" />
                        </div>
                    @endif
                </li>
            @endif
        </ul>
    </section>

    {{-- Signed-in devices --}}
    <section class="rounded-panel border border-line bg-white p-5 shadow-card md:p-7" aria-labelledby="sessions-title">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 id="sessions-title" class="font-serif text-[21px] font-semibold text-navy">{{ __('auth.sessions.title') }}</h2>
                <p class="mt-1.5 text-[14px] text-secondary">{{ __('auth.sessions.subtitle') }}</p>
            </div>
            @if ($sessions->count() > 1)
                <x-button variant="secondary" size="sm" icon="logout" wire:click="logoutOtherDevices" wire:confirm="{{ __('auth.sessions.confirm_others') }}">{{ __('auth.sessions.logout_others') }}</x-button>
            @endif
        </div>

        <ul class="mt-5 divide-y divide-line">
            @foreach ($sessions as $session)
                @php($agent = $session->agent())
                @php($current = $session->device_id === $currentDevice)
                <li class="flex items-center gap-4 py-3.5 first:pt-0 last:pb-0" wire:key="session-{{ $session->id }}">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy">
                        <x-icon :name="$agent->mobile ? 'smartphone' : 'monitor'" :size="20" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-[15px] font-semibold text-navy">
                            {{ $agent->label() ?? __('auth.sessions.unknown_device') }}
                            @if ($current)
                                <x-badge>{{ __('auth.sessions.this_device') }}</x-badge>
                            @endif
                        </p>
                        <p class="text-[13.5px] text-secondary">
                            {{ $session->ip_address }} · {{ $current ? __('auth.sessions.active_now') : app(\App\Services\DateFormatter::class)->relative($session->last_active_at) }}
                        </p>
                    </div>
                    @unless ($current)
                        <x-icon-button icon="logout" :label="__('auth.sessions.revoke')" variant="ghost" wire:click="revokeSession({{ $session->id }})" />
                    @endunless
                </li>
            @endforeach
        </ul>
    </section>
</div>
