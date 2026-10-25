<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LinkGoogleAccount;
use App\Actions\Auth\LoginWithGoogle;
use App\Exceptions\AuthFlowException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Google sign-in (one step for login and registration) and linking Google to a signed-in account.
 * The callback URL is not localized: it has to match the URI registered with Google.
 */
class GoogleController extends Controller
{
    private const string STATE_KEY = 'auth.google';

    public function redirect(Request $request): RedirectResponse|SymfonyRedirect
    {
        $linking = $request->query('intent') === 'link' && Auth::check();

        if (blank(config('services.google.client_id'))) {
            return $this->back($linking, __('auth.google.not_configured'));
        }

        if (! $linking && Auth::check()) {
            return redirect()->to(localized_route('home'));
        }

        $request->session()->put(self::STATE_KEY, [
            'intent' => $linking ? 'link' : 'login',
            'locale' => App::getLocale(),
            'remember' => $request->boolean('remember', true),
            'return_to' => LoginRedirect::isSafe($request->query('return_to')) ? $request->query('return_to') : null,
        ]);

        /** @var GoogleProvider $google */
        $google = Socialite::driver('google');

        return $google->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, LoginWithGoogle $login, LinkGoogleAccount $link, LoginRedirect $redirect): RedirectResponse
    {
        /** @var array{intent: string, locale: string, remember: bool, return_to: string|null} $state */
        $state = $request->session()->pull(self::STATE_KEY, ['intent' => 'login', 'locale' => config('locales.default'), 'remember' => true, 'return_to' => null]);
        App::setLocale($state['locale']);

        $linking = $state['intent'] === 'link' && Auth::check();

        if ($request->filled('error')) {
            return $this->back($linking, __('auth.google.cancelled'));
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::warning('Google sign-in failed.', ['error' => $exception->getMessage()]);

            return $this->back($linking, __('auth.google.failed'));
        }

        try {
            if ($linking) {
                /** @var User $user */
                $user = $request->user();
                $link->handle($user, $google);

                return redirect()->to(localized_route('account.security'))->with('status', __('auth.google.linked'));
            }

            $user = $login->handle($google);
        } catch (AuthFlowException $exception) {
            return $this->back($linking, $exception->userMessage());
        }

        Auth::guard('web')->login($user, $state['remember']);
        $request->session()->regenerate();

        return redirect()->to($redirect->for($user, $state['return_to']));
    }

    private function back(bool $linking, string $error): RedirectResponse
    {
        return redirect()->to(localized_route($linking ? 'account.security' : 'login'))->with('error', $error);
    }
}
