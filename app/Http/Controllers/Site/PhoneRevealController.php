<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ListingStats;
use App\Support\Listings\Contact;
use App\Support\Listings\ListingTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Show phone": numbers are never in the page source, only here, rate-limited (throttle:phone-reveal)
 * and counted in the owner's statistics.
 */
class PhoneRevealController extends Controller
{
    public function __invoke(Request $request, ListingStats $stats, string $type, int $id): JsonResponse
    {
        $record = ListingTypes::find($type, $id);
        $record->loadMissing(array_filter(['user', $record->getAttribute('business_profile_id') !== null ? 'businessProfile' : null]));
        $phone = Contact::phone($record);

        abort_if($phone === null, 404);

        $owner = Contact::owner($record);

        if ($owner === null || $owner->getKey() !== $request->user()?->getKey()) {
            $stats->record($record, 'phone_reveals');
        }

        return response()->json(Contact::links($phone));
    }
}
