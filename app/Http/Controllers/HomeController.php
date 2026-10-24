<?php

namespace App\Http\Controllers;

use App\Services\HomepageData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageData $data): View
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
        ]);
    }
}
