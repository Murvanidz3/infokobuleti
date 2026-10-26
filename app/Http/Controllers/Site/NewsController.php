<?php

namespace App\Http\Controllers\Site;

use App\Enums\CategoryModule;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Article;
use App\Models\Category;
use App\Services\DateFormatter;
use App\Services\Seo\StructuredData;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\ListingQuery;
use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    use ShowsListing;

    public function index(Request $request, CardPresenter $cards): View
    {
        $category = Category::query()->forModule(CategoryModule::News)->where('slug', (string) $request->query('category'))->first();

        $articles = ListingQuery::public(Article::query())
            ->with(['media', 'category'])
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('pages.news.index', [
            'category' => $category,
            'categories' => Category::query()->forModule(CategoryModule::News)->where('is_active', true)->orderBy('sort')->get(),
            'articles' => $articles,
            'cards' => $articles->getCollection()->map(fn (Article $article) => $cards->news($article)),
        ]);
    }

    public function show(Article $article, CardPresenter $cards, DateFormatter $dates, StructuredData $seo): View
    {
        $preview = $this->visit($article);
        $article->load(['media', 'category', 'author']);

        $related = ListingQuery::public(Article::query())
            ->with(['media', 'category'])
            ->whereKeyNot($article->id)
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [(int) $article->category_id])
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Article $item) => $cards->news($item));

        $body = SafeHtml::clean((string) $article->body);

        return view('pages.news.show', [
            'seo' => $seo->for($article),
            'article' => $article,
            'preview' => $preview,
            'body' => $body,
            'date' => $dates->date($article->published_at ?? $article->created_at ?? now()),
            'readingMinutes' => max(1, (int) round(preg_match_all('/\p{L}+/u', strip_tags($body)) / 200)),
            'image' => $article->getFirstMedia('gallery')?->getAvailableUrl(['large', 'card']),
            'related' => $related,
            'description' => $cards->excerpt($article->excerpt ?: $article->body, 160),
        ]);
    }
}
