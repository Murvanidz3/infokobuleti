<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Account\OwnerListings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The "promote" page of one of the owner's listings (VIP, Super VIP, bumps).
 */
class PromoteListingController extends Controller
{
    public function __invoke(Request $request, string $type, int $id, OwnerListings $listings): View
    {
        /** @var User $user */
        $user = $request->user();
        $listing = $listings->find($user, $type, $id);

        return view('pages.account.promote', ['type' => $type, 'id' => (int) $listing->getKey()]);
    }
}
