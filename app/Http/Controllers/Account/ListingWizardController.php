<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Pages of the add-listing wizard; everything else happens in App\Livewire\ListingWizard.
 */
class ListingWizardController extends Controller
{
    public function create(): View
    {
        return view('pages.listings.wizard', ['editType' => null, 'editId' => null]);
    }

    public function edit(string $type, int $id): View
    {
        return view('pages.listings.wizard', ['editType' => $type, 'editId' => $id]);
    }
}
