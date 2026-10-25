<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Auth\PendingStaffLogin;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * First staff sign-in: two-factor authentication must be set up before the account can be used.
 */
class TwoFactorSetupController extends Controller
{
    public function create(PendingStaffLogin $pending, TwoFactorAuthenticator $twoFactor): View|RedirectResponse
    {
        $user = $pending->user();

        if ($user === null) {
            return redirect()->to(localized_route('staff.login'));
        }

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->to(localized_route('staff.two-factor'));
        }

        $secret = $pending->setupSecret(fn () => $twoFactor->generateSecret());

        return view('pages.staff.two-factor-setup', [
            'qrCode' => $twoFactor->qrCodeSvg((string) $user->email, $secret),
            'secret' => TwoFactorAuthenticator::formatSecret($secret),
        ]);
    }

    public function store(Request $request, PendingStaffLogin $pending, TwoFactorAuthenticator $twoFactor): RedirectResponse
    {
        $user = $pending->user();

        if ($user === null || $user->hasTwoFactorEnabled()) {
            return redirect()->to(localized_route('staff.login'))->with('error', __('auth.staff.expired'));
        }

        $request->validate(['code' => ['required', 'string', 'max:16']]);

        $secret = $pending->setupSecret(fn () => $twoFactor->generateSecret());

        if (! $twoFactor->verify($secret, $request->string('code')->toString(), $user->id)) {
            throw ValidationException::withMessages(['code' => __('auth.staff.invalid_code')]);
        }

        $codes = $twoFactor->enable($user, $secret);
        $remember = $pending->remember();
        $pending->clear();

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        return redirect()->to(localized_route('staff.recovery-codes'))->with('recovery_codes', $codes);
    }
}
