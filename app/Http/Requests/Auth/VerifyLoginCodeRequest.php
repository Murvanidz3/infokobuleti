<?php

namespace App\Http\Requests\Auth;

use App\Rules\GeorgianMobile;
use App\Support\GeorgianPhone;
use Illuminate\Foundation\Http\FormRequest;

class VerifyLoginCodeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32', new GeorgianMobile],
            'code' => ['required', 'string', 'regex:/^\s*\d{'.(int) config('otp.length').'}\s*$/'],
            'remember' => ['sometimes', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.regex' => __('auth.otp.code_format', ['length' => (int) config('otp.length')])];
    }

    public function phone(): string
    {
        return (string) GeorgianPhone::normalize($this->string('phone')->toString());
    }
}
