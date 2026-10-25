<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Auth\PendingStaffLogin;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Step 2 of staff sign-in: a code from the authenticator app, or a recovery code.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(PendingStaffLogin $pending): View|RedirectResponse
    {
        $user = $pending->user();

        if ($user === null || ! $user->hasTwoFactorEnabled()) {
            return redirect()->to(localized_route('staff.login'));
        }

        return view('pages.staff.two-factor-challenge');
    }

    public function store(Request $request, PendingStaffLogin $pending, TwoFactorAuthenticator $twoFactor): RedirectResponse
    {
        $user = $pending->user();

        if ($user === null || ! $user->hasTwoFactorEnabled()) {
            return redirect()->to(localized_route('staff.login'))->with('error', __('auth.staff.expired'));
        }

        $request->validate([
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'max:16'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        $key = 'staff-2fa:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        $valid = $request->filled('recovery_code')
            ? $twoFactor->useRecoveryCode($user, $request->string('recovery_code')->toString())
            : $twoFactor->verify((string) $user->two_factor_secret, $request->string('code')->toString(), $user->id);

        if (! $valid) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                $request->filled('recovery_code') ? 'recovery_code' : 'code' => __('auth.staff.invalid_code'),
            ]);
        }

        RateLimiter::clear($key);
        $remember = $pending->remember();
        $pending->clear();

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        $status = $request->filled('recovery_code')
            ? trans_choice('auth.staff.recovery_used', count($user->two_factor_recovery_codes ?? []), ['count' => count($user->two_factor_recovery_codes ?? [])])
            : null;

        return redirect()->intended(localized_route('account.security'))->with('status', $status);
    }
}
