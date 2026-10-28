<?php

namespace App\Rules;

use App\Support\GeorgianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeorgianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! GeorgianPhone::isValid($value)) {
            $fail(__('auth.phone.invalid'));
        }
    }
}
