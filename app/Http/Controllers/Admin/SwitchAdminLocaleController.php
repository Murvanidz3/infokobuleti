<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetAdminLocale;
use Illuminate\Http\RedirectResponse;

class SwitchAdminLocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        SetAdminLocale::set($locale);

        return redirect()->back(fallback: route('filament.admin.pages.dashboard'));
    }
}
