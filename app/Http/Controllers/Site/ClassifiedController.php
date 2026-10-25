<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ShowsListing;
use App\Models\Classified;
use App\Services\Favorites;
use App\Support\AttributeSchema;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\Contact;
use App\Support\Listings\DetailPresenter;
use App\Support\Listings\ListingQuery;
use Illuminate\View\View;

class ClassifiedController extends Controller
{
    use ShowsListing;

    public function show(Classified $classified, CardPresenter $cards, DetailPresenter $detail, Favorites $favorites): View
    {
        $preview = $this->visit($classified);
        $classified->load(['media', 'location', 'businessProfile', 'user', 'category.parent']);

        // Attribute values shown with the category schema's labels and units.
        $attributes = [];

        foreach ($classified->category?->schema()->fields() ?? [] as $field) {
            $value = $classified->attribute_values[$field['key']] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $choices = AttributeSchema::choices($field);
            $text = match ($field['type']) {
                'boolean' => $value ? __('site.filters.yes') : __('site.filters.no'),
                'select' => $choices[$value] ?? (string) $value,
                'multiselect' => implode(', ', array_map(fn ($item): string => (string) ($choices[$item] ?? $item), (array) $value)),
                default => (string) $value.(isset($field['unit']) ? ' '.$field['unit'] : ''),
            };

            $attributes[] = ['icon' => 'tag', 'label' => $field['label'][app()->getLocale()] ?? $field['label']['ka'] ?? $field['key'], 'value' => $text];
        }

        if ($classified->condition !== null) {
            array_unshift($attributes, ['icon' => 'check', 'label' => __('site.filters.condition'), 'value' => (string) $classified->condition->getLabel()]);
        }

        $similar = ListingQuery::public(Classified::query())
            ->with(['media', 'location', 'category.parent'])
            ->whereKeyNot($classified->id)
            ->where('category_id', $classified->category_id)
            ->orderByPromotion()
            ->limit(4)
            ->get()
            ->map(fn (Classified $item): array => $cards->classified($item));

        return view('pages.classifieds.show', [
            'classified' => $classified,
            'preview' => $preview,
            'gallery' => $detail->gallery($classified),
            'facts' => $attributes,
            'price' => $cards->classifiedPrice($classified),
            'owner' => $detail->owner($classified),
            'hasPhone' => Contact::phone($classified) !== null,
            'similar' => $similar,
            'saved' => $favorites->isSaved($classified),
            'address' => $cards->locationText($classified),
            'posted' => $classified->published_at,
            'description' => $detail->description($classified),
        ]);
    }
}
