<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Project;
use App\Services\Favorites;
use App\Services\PriceFormatter;
use App\Services\Seo\StructuredData;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ShowsListing;

    public function index(): View
    {
        return view('pages.real-estate.projects.index', [
            'title' => __('site.projects.title'),
            'subtitle' => __('site.projects.subtitle'),
        ]);
    }

    public function show(Project $project, CardPresenter $cards, DetailPresenter $detail, PriceFormatter $price, Favorites $favorites, StructuredData $seo): View
    {
        $preview = $this->visit($project);
        $project->load(['media', 'location', 'businessProfile', 'user']);

        $similar = ListingQuery::public(Project::query())
            ->with(['media', 'location'])
            ->whereKeyNot($project->id)
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (Project $item): array => $cards->project($item));

        $currency = $cards->currency($project->currency);
        $facts = array_values(array_filter([
            ['icon' => 'building', 'label' => __('site.projects.developer'), 'value' => (string) $project->developer],
            $project->completion_on !== null ? ['icon' => 'calendar', 'label' => __('site.projects.completion'), 'value' => $project->completion_on->format('m.Y')] : null,
            ['icon' => 'tag', 'label' => __('site.projects.price_from_label'), 'value' => $price->format((float) $project->price_per_m2, $currency).' / m²'],
            $project->distance_to_sea_m !== null ? ['icon' => 'waves', 'label' => __('site.real_estate.to_sea'), 'value' => (string) $cards->sea($project->distance_to_sea_m)] : null,
        ]));

        return view('pages.real-estate.projects.show', [
            'seo' => $seo->for($project),
            'project' => $project,
            'card' => $cards->project($project),
            'preview' => $preview,
            'gallery' => $detail->gallery($project),
            'facts' => $facts,
            'owner' => $detail->owner($project),
            'hasPhone' => Contact::phone($project) !== null,
            'similar' => $similar,
            'saved' => $favorites->isSaved($project),
            'description' => $detail->description($project),
        ]);
    }
}
