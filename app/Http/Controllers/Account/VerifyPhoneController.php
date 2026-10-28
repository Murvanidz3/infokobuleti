<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Asks accounts without a verified phone (Google sign-ins) to add one before posting.
 */
class VerifyPhoneController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.account.phone');
    }
}
