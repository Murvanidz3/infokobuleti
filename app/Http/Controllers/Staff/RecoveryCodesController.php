<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shows freshly generated recovery codes once (they arrive in the flash data).
 */
class RecoveryCodesController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('recovery_codes');

        if (! is_array($codes) || $codes === []) {
            return redirect()->to(localized_route('account.security'));
        }

        return view('pages.staff.recovery-codes', ['codes' => $codes]);
    }
}
