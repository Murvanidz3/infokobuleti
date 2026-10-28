<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StaffLoginRequest;
use App\Models\User;
use App\Services\Auth\PendingStaffLogin;
use App\Support\AuthLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Step 1 of staff sign-in: email and password. Step 2 is the authenticator code.
 */
class StaffLoginController extends Controller
{
    public function create(): View
    {
        return view('pages.staff.login');
    }

    public function store(StaffLoginRequest $request, PendingStaffLogin $pending): RedirectResponse
    {
        $key = 'staff-login:'.Str::lower($request->string('email')->toString()).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        $user = User::query()->where('email', Str::lower($request->string('email')->toString()))->first();

        if ($user === null || $user->password === null || ! Hash::check($request->string('password')->toString(), $user->password) || ! $user->isStaff()) {
            AuthLog::failed($request, 'staff-password');
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->is_blocked) {
            throw ValidationException::withMessages(['email' => __('auth.blocked')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $pending->start($user, $request->boolean('remember'));

        return redirect()->to(localized_route($user->hasTwoFactorEnabled() ? 'staff.two-factor' : 'staff.two-factor.setup'));
    }
}
