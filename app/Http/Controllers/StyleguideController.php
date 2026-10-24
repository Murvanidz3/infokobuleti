<?php

namespace App\Http\Controllers;

use App\Services\HomepageData;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class StyleguideController extends Controller
{
    /**
     * Every design-system component and variant on one page. Not available in production.
     */
    public function __invoke(Request $request, HomepageData $data): View
    {
        abort_if(app()->isProduction(), 404);

        return view('pages.styleguide', [
            'categories' => $data->categories(),
            'hotels' => $data->hotels(),
            'properties' => $data->properties(),
            'classifieds' => $data->classifieds(),
            'jobs' => $data->jobs(),
            'guide' => $data->guide(),
            'info' => $data->usefulInfo(),
            'news' => $data->news(),
            'paginator' => new LengthAwarePaginator(
                items: [],
                total: 240,
                perPage: 24,
                currentPage: max(1, $request->integer('page', 4)),
                options: ['path' => $request->url(), 'pageName' => 'page'],
            ),
        ]);
    }
}
