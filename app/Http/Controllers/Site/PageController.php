<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SafeHtml;
use Illuminate\View\View;

/**
 * Static pages edited in the admin (about, terms, privacy…).
 */
class PageController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless($page->is_published || auth()->user()?->isStaff(), 404);

        return view('pages.static', [
            'page' => $page,
            'body' => SafeHtml::clean((string) $page->body),
            'description' => $page->meta_description ? (string) $page->meta_description : null,
        ]);
    }
}
