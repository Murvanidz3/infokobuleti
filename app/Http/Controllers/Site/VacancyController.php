<?php

namespace App\Http\Controllers\Site;

use App\Enums\ApplyMethod;
use App\Enums\VacancyBenefit;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Favorites;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\View;

class VacancyController extends Controller
{
    use ShowsListing;

    public function show(Vacancy $vacancy, CardPresenter $cards, DetailPresenter $detail, Favorites $favorites): View
    {
        $preview = $this->visit($vacancy);
        $vacancy->load(['location', 'businessProfile', 'user', 'category']);
        $user = auth()->user();

        $facts = array_values(array_filter([
            ['icon' => 'briefcase', 'label' => __('home.search.fields.employment'), 'value' => (string) $vacancy->employment_type->getLabel()],
            ['icon' => 'tag', 'label' => __('site.jobs.salary'), 'value' => trim($cards->salary($vacancy).' '.($vacancy->salary_min !== null || $vacancy->salary_max !== null ? $cards->salaryPeriod($vacancy->salary_period) : ''))],
            $vacancy->category ? ['icon' => 'grid', 'label' => __('home.search.fields.category'), 'value' => (string) $vacancy->category->name] : null,
            $vacancy->experience ? ['icon' => 'star', 'label' => __('site.jobs.experience'), 'value' => (string) $vacancy->experience->getLabel()] : null,
            $vacancy->languages ? ['icon' => 'globe', 'label' => __('site.jobs.languages'), 'value' => collect($vacancy->languages)->map(fn ($code) => Lang::has('site.languages.'.$code) ? __('site.languages.'.$code) : mb_strtoupper((string) $code))->implode(', ')] : null,
            $vacancy->deadline ? ['icon' => 'calendar', 'label' => __('site.jobs.deadline'), 'value' => $vacancy->deadline->format('d.m.Y')] : null,
        ]));

        $similar = ListingQuery::public(Vacancy::query())
            ->with(['location', 'businessProfile'])
            ->whereKeyNot($vacancy->id)
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$vacancy->category_id])
            ->orderByPromotion()
            ->limit(5)
            ->get()
            ->map(fn (Vacancy $item): array => $cards->job($item));

        return view('pages.jobs.show', [
            'vacancy' => $vacancy,
            'preview' => $preview,
            'facts' => $facts,
            'benefits' => collect($vacancy->benefits ?? [])->map(fn ($benefit) => VacancyBenefit::tryFrom((string) $benefit)?->getLabel())->filter()->values()->all(),
            'owner' => $detail->owner($vacancy),
            'hasPhone' => Contact::phone($vacancy) !== null,
            'similar' => $similar,
            'saved' => $favorites->isSaved($vacancy),
            'address' => $cards->locationText($vacancy),
            'appliesOnSite' => $vacancy->apply_method === ApplyMethod::Site,
            'applied' => $user instanceof User && $vacancy->applications()->where('user_id', $user->id)->exists(),
            'salary' => $cards->salary($vacancy),
            'description' => $detail->description($vacancy),
        ]);
    }
}
