<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Room types, seasonal prices and the availability calendar of one of the owner's hotels.
 */
class HotelRoomsController extends Controller
{
    public function __invoke(Request $request, int $hotel): View
    {
        $model = Hotel::query()->where('user_id', $request->user()?->getKey())->findOrFail($hotel);

        return view('pages.account.hotel-rooms', ['hotel' => $model]);
    }
}
