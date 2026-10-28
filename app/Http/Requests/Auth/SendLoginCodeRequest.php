<?php

namespace App\Http\Requests\Auth;

use App\Contracts\HumanVerifier;
use App\Rules\GeorgianMobile;
use App\Support\GeorgianPhone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SendLoginCodeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32', new GeorgianMobile],
            'captcha_token' => ['nullable', 'string', 'max:4096', function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(HumanVerifier::class)->verify(is_string($value) ? $value : null, $this->ip())) {
                    $fail(__('auth.captcha_failed'));
                }
            }],
        ];
    }

    public function phone(): string
    {
        return (string) GeorgianPhone::normalize($this->string('phone')->toString());
    }
}
