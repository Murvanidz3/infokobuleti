<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\HumanVerifier;
use App\Http\Controllers\Controller;
use App\Services\Auth\LoginRedirect;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Full-page sign-in (mobile, and wherever the header dialog is not used).
     */
    public function __invoke(Request $request, HumanVerifier $captcha): View
    {
        $returnTo = $request->query('return_to');

        if (LoginRedirect::isSafe($returnTo)) {
            $request->session()->put('url.intended', $returnTo);
        }

        return view('pages.auth.login', ['captcha' => $captcha]);
    }
}
