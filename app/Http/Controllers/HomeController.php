<?php

namespace App\Http\Controllers;

use App\Services\HomepageData;
use App\Services\Seo\StructuredData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageData $data, StructuredData $seo): View
    {
        return view('pages.home', [
            'categories' => $data->categories(),
            'hotels' => $data->hotels(),
            'properties' => $data->properties(),
            'classifieds' => $data->classifieds(),
            'jobs' => $data->jobs(),
            'guide' => $data->guide(),
            'info' => $data->usefulInfo(),
            'news' => $data->news(),
            'events' => $data->events(),
            'sections' => $data->sections(),
            'heroImage' => $data->heroImage(),
            'searchOptions' => $data->searchOptions(),
            'schema' => $seo->site(),
        ]);
    }
}
