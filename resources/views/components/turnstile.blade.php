@php
    $captcha = app(\App\Contracts\HumanVerifier::class);
@endphp

{{-- Cloudflare Turnstile for public forms; renders nothing while it is not configured. --}}
@if ($captcha->enabled())
    <div {{ $attributes->class(['cf-turnstile min-h-[65px]']) }} data-sitekey="{{ $captcha->siteKey() }}" data-language="{{ app()->getLocale() }}" data-size="flexible"></div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
@error('cf-turnstile-response', $bag ?? 'default')
    <p class="mt-2 text-[13.5px] text-coral-pressed">{{ $message }}</p>
@enderror
