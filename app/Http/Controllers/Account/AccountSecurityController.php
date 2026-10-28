<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.account.security');
    }
}
