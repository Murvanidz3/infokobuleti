<?php

use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsureUserIsOnboarded;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\LogoutBlockedUsers;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackUserSessions;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            LogoutBlockedUsers::class,
            TrackUserSessions::class,
        ]);

        $middleware->alias([
            'phone.verified' => EnsurePhoneIsVerified::class,
            'onboarded' => EnsureUserIsOnboarded::class,
            'staff' => EnsureUserIsStaff::class,
        ]);

        // The route's language is applied first, so auth redirects and messages use it too.
        $middleware->prependToPriorityList(EncryptCookies::class, SetLocale::class);

        $middleware->redirectGuestsTo(fn (Request $request) => localized_route(
            $request->routeIs('staff.*', '*.staff.*') ? 'staff.login' : 'login',
        ));
        $middleware->redirectUsersTo(fn () => localized_route('home'));

        $middleware->validateCsrfTokens(except: ['webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
