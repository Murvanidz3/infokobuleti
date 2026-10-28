<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user, Impersonation $impersonation): RedirectResponse
    {
        /** @var User $staff */
        $staff = $request->user();
        $impersonation->start($staff, $user);

        return redirect()->to(localized_route('home'));
    }

    public function leave(Impersonation $impersonation): RedirectResponse
    {
        $staff = $impersonation->leave();

        return $staff === null
            ? redirect()->to(localized_route('home'))
            : redirect()->route('filament.admin.resources.users.index');
    }
}
