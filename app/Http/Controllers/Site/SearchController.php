<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Search\SiteSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request, SiteSearch $search): View
    {
        $query = trim((string) $request->query('q', ''));
        $module = (string) $request->query('module', '');
        $groups = $search->grouped($query, array_key_exists($module, SiteSearch::MODULES) ? 24 : 6);

        if (array_key_exists($module, SiteSearch::MODULES)) {
            $groups = array_values(array_filter($groups, fn (array $group): bool => $group['module'] === $module));
        }

        return view('pages.search', [
            'query' => $query,
            'groups' => $groups,
            'module' => $module,
        ]);
    }

    public function suggest(Request $request, SiteSearch $search): JsonResponse
    {
        return response()->json(['groups' => $search->grouped((string) $request->query('q', ''), 3)])
            ->header('Cache-Control', 'private, max-age=30');
    }
}
