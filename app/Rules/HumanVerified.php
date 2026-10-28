<?php

namespace App\Rules;

use App\Contracts\HumanVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the Cloudflare Turnstile token of a public form (always passes when Turnstile is off).
 */
class HumanVerified implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verifier = app(HumanVerifier::class);

        if (! $verifier->verify(is_string($value) ? $value : null, request()->ip())) {
            $fail(__('site.form.captcha_failed'));
        }
    }
}
