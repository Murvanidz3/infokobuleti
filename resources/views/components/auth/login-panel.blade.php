@props([
    'returnTo' => null,
])

@php
    $captcha = app(\App\Contracts\HumanVerifier::class);
    $config = [
        'sendUrl' => localized_route('auth.phone.send'),
        'verifyUrl' => localized_route('auth.phone.verify'),
        'googleUrl' => localized_route('auth.google', array_filter(['return_to' => $returnTo])),
        'returnTo' => $returnTo,
        'turnstile' => $captcha->enabled() ? $captcha->siteKey() : null,
        'locale' => app()->getLocale(),
        'messages' => [
            'resend' => __('auth.otp.resend'),
            'resendIn' => __('auth.otp.resend_in'),
            'invalidPhone' => __('auth.phone.invalid'),
            'failed' => __('auth.login.failed'),
            'network' => __('auth.login.network'),
        ],
    ];
@endphp

<div x-data="phoneLogin(@js($config))" {{ $attributes->class(['flex flex-col']) }}>
    {{-- Step 1: Google or phone number --}}
    <div x-show="step === 'phone'" class="flex flex-col gap-5">
        <a href="{{ $config['googleUrl'] }}"
           x-bind:href="googleHref"
           class="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-button border border-line-strong bg-white px-5 text-[15px] font-semibold text-navy transition-colors duration-200 hover:border-navy hover:bg-surface-alt">
            <x-auth.google-logo />
            {{ __('auth.login.google') }}
        </a>

        <div class="flex items-center gap-4 text-[13px] font-medium text-secondary" aria-hidden="true">
            <span class="h-px flex-1 bg-line"></span>{{ __('auth.login.or') }}<span class="h-px flex-1 bg-line"></span>
        </div>

        <form x-on:submit.prevent="send" class="flex flex-col gap-4" novalidate>
            <x-form.field :label="__('auth.phone.label')" for="login-phone">
                <div class="flex min-h-12 items-stretch overflow-hidden rounded-field border border-line bg-white transition-colors duration-200 focus-within:border-navy hover:border-line-strong"
                     x-bind:class="error && step === 'phone' ? 'border-coral-pressed' : ''">
                    <span class="flex items-center gap-2 border-r border-line bg-surface-alt px-3.5 text-[15px] font-semibold text-navy">+995</span>
                    <input id="login-phone"
                           x-ref="phone"
                           x-model="phone"
                           x-on:input="onPhoneInput"
                           type="tel"
                           inputmode="numeric"
                           autocomplete="tel-national"
                           placeholder="5XX XX XX XX"
                           maxlength="12"
                           aria-describedby="login-error"
                           class="min-w-0 flex-1 border-0 bg-transparent px-4 text-[16px] tracking-[0.02em] text-navy outline-none placeholder:text-secondary/60">
                </div>
            </x-form.field>

            @if ($config['turnstile'])
                <div x-ref="captcha" class="min-h-[65px]"></div>
            @endif

            <label class="inline-flex cursor-pointer items-center gap-3 text-[14px] text-navy">
                <input type="checkbox" x-model="remember" class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">
                {{ __('auth.login.remember') }}
            </label>

            <x-button type="submit" size="lg" class="w-full" x-bind:disabled="busy">
                <span x-show="!busy">{{ __('auth.otp.send') }}</span>
                <span x-show="busy" x-cloak>{{ __('components.loading') }}</span>
            </x-button>
        </form>
    </div>

    {{-- Step 2: the SMS code --}}
    <form x-show="step === 'code'" x-cloak x-on:submit.prevent="verify" class="flex flex-col gap-4" novalidate>
        <p class="text-[15px] leading-[1.6] text-secondary">
            {{ __('auth.otp.sent_to') }}
            <strong class="font-semibold whitespace-nowrap text-navy" x-text="sentTo"></strong>
            <button type="button" x-on:click="back" class="ml-1 font-semibold text-coral-pressed underline-offset-4 hover:underline">{{ __('auth.otp.change_number') }}</button>
        </p>

        <template x-if="devCode">
            <p class="rounded-tile bg-surface-alt px-4 py-3 text-[14px] text-secondary">
                {{ __('auth.otp.dev_code') }} <strong class="font-semibold tracking-[0.2em] text-navy" x-text="devCode"></strong>
            </p>
        </template>

        <x-form.field :label="__('auth.otp.code_label')" for="login-code">
            <input id="login-code"
                   x-ref="code"
                   x-model="code"
                   x-on:input="onCodeInput"
                   type="text"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   pattern="\d{6}"
                   maxlength="6"
                   placeholder="••••••"
                   aria-describedby="login-error"
                   class="block min-h-14 w-full rounded-field border border-line bg-white px-4 text-center text-[24px] font-semibold tracking-[0.5em] text-navy transition-colors duration-200 placeholder:text-secondary/40 hover:border-line-strong focus:border-navy"
                   x-bind:class="error && step === 'code' ? 'border-coral-pressed' : ''">
        </x-form.field>

        <x-button type="submit" size="lg" class="w-full" x-bind:disabled="busy || code.length !== 6">
            <span x-show="!busy">{{ __('auth.otp.verify') }}</span>
            <span x-show="busy" x-cloak>{{ __('components.loading') }}</span>
        </x-button>

        <button type="button"
                x-on:click="resend"
                x-bind:disabled="resendIn > 0 || busy"
                x-text="resendLabel"
                class="hit-44 relative self-center text-[14px] font-semibold text-navy underline-offset-4 hover:underline disabled:cursor-default disabled:font-medium disabled:text-secondary disabled:no-underline">{{ __('auth.otp.resend') }}</button>
    </form>

    <p id="login-error" x-show="error" x-cloak x-text="error" role="alert" class="mt-4 flex items-center gap-1.5 text-[13.5px] font-medium text-coral-pressed"></p>
</div>
