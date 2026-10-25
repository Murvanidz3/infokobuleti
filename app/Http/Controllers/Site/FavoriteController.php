<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Favorites;
use App\Support\Listings\ListingTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __invoke(Request $request, Favorites $favorites, string $type, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => __('site.favorites.sign_in')], 401);
        }

        $saved = $favorites->toggle($user, ListingTypes::find($type, $id));

        return response()->json([
            'saved' => $saved,
            'message' => __($saved ? 'site.favorites.saved' : 'site.favorites.removed'),
        ]);
    }
}
