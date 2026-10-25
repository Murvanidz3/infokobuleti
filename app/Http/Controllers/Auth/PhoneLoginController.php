<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithPhone;
use App\Enums\OtpPurpose;
use App\Exceptions\AuthFlowException;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLoginCodeRequest;
use App\Http\Requests\Auth\VerifyLoginCodeRequest;
use App\Services\Auth\LoginRedirect;
use App\Services\Auth\PhoneOtp;
use App\Support\GeorgianPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * JSON endpoints behind the phone sign-in form (header dialog and /login page).
 */
class PhoneLoginController extends Controller
{
    public function send(SendLoginCodeRequest $request, PhoneOtp $otp): JsonResponse
    {
        $phone = $request->phone();

        try {
            $sent = $otp->send($phone, OtpPurpose::Login, $request->ip());
        } catch (OtpException $exception) {
            return response()->json([
                'message' => $exception->userMessage(),
                'retry_after' => $exception->retryAfter,
            ], 429);
        }

        return response()->json(array_filter([
            'phone' => GeorgianPhone::format($phone),
            'resend_in' => $sent->resendAfter,
            'expires_in' => (int) config('otp.ttl'),
            'message' => __('auth.otp.sent', ['phone' => GeorgianPhone::format($phone)]),
            'dev_code' => $sent->revealedCode,
        ], fn ($value) => $value !== null));
    }

    public function verify(VerifyLoginCodeRequest $request, PhoneOtp $otp, LoginWithPhone $login, LoginRedirect $redirect): JsonResponse
    {
        $phone = $request->phone();

        try {
            $otp->verify($phone, OtpPurpose::Login, $request->string('code')->toString(), $request->ip());
            $user = $login->handle($phone);
        } catch (OtpException $exception) {
            return response()->json([
                'message' => $exception->userMessage(),
                'errors' => ['code' => [$exception->userMessage()]],
                'restart' => in_array($exception->error->value, ['expired', 'too_many_attempts'], true),
            ], 422);
        } catch (AuthFlowException $exception) {
            return response()->json(['message' => $exception->userMessage()], 403);
        }

        Auth::guard('web')->login($user, $request->boolean('remember', true));
        $request->session()->regenerate();

        return response()->json([
            'redirect' => $redirect->for($user, $request->input('return_to')),
        ]);
    }
}
