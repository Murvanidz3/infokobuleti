<?php

use App\Enums\ListingStatus;
use App\Livewire\Listings\HotelIndex;
use App\Models\Article;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Vacancy;
use App\Search\SiteSearch;
use App\Services\Settings;
use Livewire\Livewire;

function searchTitles(string $query, string $module = 'hotels'): array
{
    $group = collect(app(SiteSearch::class)->grouped($query, 20))->firstWhere('module', $module);

    return collect($group['items'] ?? [])->pluck('title')->sort()->values()->all();
}

beforeEach(function () {
    config(['scout.driver' => 'pgsql']);
});

it('finds listings by whole words and prefixes in any language', function () {
    Hotel::factory()->create(['title' => ['ka' => 'სასტუმრო ზღვისპირა', 'en' => 'Seaside Hotel', 'ru' => 'Отель у моря'], 'description' => ['en' => 'Pool and garden']]);
    Hotel::factory()->create(['title' => ['ka' => 'მთის სახლი', 'en' => 'Mountain House', 'ru' => 'Горный дом'], 'description' => ['en' => 'Quiet place']]);

    expect(searchTitles('seaside'))->toBe(['სასტუმრო ზღვისპირა'])
        ->and(searchTitles('sea'))->toBe(['სასტუმრო ზღვისპირა'])
        ->and(searchTitles('ზღვის'))->toBe(['სასტუმრო ზღვისპირა'])
        ->and(searchTitles('Горный'))->toBe(['მთის სახლი'])
        ->and(searchTitles('garden'))->toBe(['სასტუმრო ზღვისპირა']);
});

it('tolerates typos', function () {
    Hotel::factory()->create(['title' => ['ka' => 'Sunrise', 'en' => 'Sunrise Hotel']]);
    Hotel::factory()->create(['title' => ['ka' => 'Moonlight', 'en' => 'Moonlight Guesthouse']]);

    expect(searchTitles('Sunrize'))->toBe(['Sunrise'])
        ->and(searchTitles('moonlihgt'))->toBe(['Moonlight'])
        ->and(searchTitles('guesthose'))->toBe(['Moonlight']);
});

it('uses the synonyms edited in the admin', function () {
    Place::factory()->create(['title' => ['ka' => 'ცენტრალური პლაჟი', 'en' => 'Central beach'], 'description' => ['ka' => 'ტესტი', 'en' => 'Test'], 'type' => 'beach']);

    expect(searchTitles('seashore', 'guide'))->toBe([]);

    app(Settings::class)->put(['search.synonyms' => "seashore, beach, пляж\nhotel, სასტუმრო"]);

    expect(searchTitles('seashore', 'guide'))->toBe(['ცენტრალური პლაჟი']);
});

it('never returns hidden or expired items', function () {
    Hotel::factory()->create(['title' => ['ka' => 'Hidden draft hotel', 'en' => 'Hidden draft hotel'], 'status' => ListingStatus::Draft, 'published_at' => null]);
    Hotel::factory()->create(['title' => ['ka' => 'Hidden expired hotel', 'en' => 'Hidden expired hotel'], 'expires_at' => now()->subDay()]);
    Vacancy::factory()->pending()->create(['title' => ['ka' => 'Hidden job', 'en' => 'Hidden job']]);
    Article::factory()->draft()->create(['title' => ['ka' => 'Hidden article', 'en' => 'Hidden article']]);

    expect(app(SiteSearch::class)->grouped('hidden'))->toBe([]);

    $this->getJson('/search/suggest?q=hidden')->assertOk()->assertExactJson(['groups' => []]);
    $this->get('/en/search?q=hidden')->assertOk()->assertDontSee('Hidden draft hotel')->assertSee(__('site.search.nothing', [], 'en'));
});

it('groups instant suggestions by module', function () {
    Hotel::factory()->create(['title' => ['ka' => 'Kobuleti Palace', 'en' => 'Kobuleti Palace']]);
    Place::factory()->create(['title' => ['ka' => 'Kobuleti beach', 'en' => 'Kobuleti beach']]);

    $groups = $this->getJson('/en/search/suggest?q=kobuleti')->assertOk()->json('groups');

    expect(array_column($groups, 'module'))->toBe(['hotels', 'guide'])
        ->and($groups[0]['items'][0])->toHaveKeys(['title', 'url', 'image', 'meta'])
        ->and($groups[0]['all'])->toBe(localized_route('hotels.index', ['q' => 'kobuleti'], 'en'));
});

it('ignores queries shorter than two letters', function () {
    Hotel::factory()->create();

    $this->getJson('/search/suggest?q=a')->assertOk()->assertExactJson(['groups' => []]);
});

it('searches inside a module list page, best match first', function () {
    Hotel::factory()->create(['title' => ['ka' => 'Blue Lagoon', 'en' => 'Blue Lagoon']]);
    Hotel::factory()->create(['title' => ['ka' => 'Green Garden', 'en' => 'Green Garden']]);

    $component = Livewire::test(HotelIndex::class)->set('q', 'lagon');

    expect(collect($component->viewData('items'))->pluck('name')->all())->toBe(['Blue Lagoon']);
});
