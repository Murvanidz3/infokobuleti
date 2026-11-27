<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Old hotel URLs. /hotels goes to daily-rent hotel properties. /hotels/{slug} goes to the
 * property copied from that hotel (archive_hotel_properties), or to the same index when the
 * slug was never a hotel.
 */
class HotelArchiveRedirectController extends Controller
{
    public function __invoke(?string $slug = null): RedirectResponse
    {
        if ($slug === null || $slug === '') {
            return redirect()->to(daily_rent_hotels_url(), 301);
        }

        $propertySlug = DB::table('archive_hotel_properties')->where('hotel_slug', $slug)->value('property_slug');

        if (! is_string($propertySlug) || $propertySlug === '') {
            return redirect()->to(daily_rent_hotels_url(), 301);
        }

        return redirect()->to(localized_route('real-estate.show', ['property' => $propertySlug]), 301);
    }
}
