<?php

use App\Enums\ListingStatus;
use App\Models\Article;
use App\Models\Event;
use App\Models\Property;
use App\Models\Vacancy;
use App\Services\Settings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * @return list<array<string, mixed>>
 */
function jsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $matches);

    return array_map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

/**
 * @return array<string, mixed>
 */
function schemaOfType(string $html, string $type): array
{
    return collect(jsonLd($html))->firstWhere('@type', $type) ?? [];
}

it('describes a property for search engines and social networks', function () {
    $property = Property::factory()->published()->create(['price' => 150, 'currency' => 'GEL']);

    $html = $this->get(localized_route('real-estate.show', [$property]))->assertOk()->getContent();
    $schema = schemaOfType($html, 'RealEstateListing');

    expect($schema)->not->toBe([])
        ->and($schema['name'])->toBe($property->title)
        ->and($schema['about']['address']['addressCountry'])->toBe('GE')
        ->and($schema)->not->toHaveKey('telephone')
        ->and(schemaOfType($html, 'BreadcrumbList'))->not->toBe([]);

    expect($html)
        ->toContain('<link rel="canonical" href="'.localized_route('real-estate.show', [$property]).'">')
        ->toContain('hreflang="x-default"')
        ->toContain('property="og:image" content="'.url("/og/property/{$property->id}-ka.jpg"))
        ->toContain('name="twitter:card" content="summary_large_image"');
});

it('marks up jobs for Google Jobs', function () {
    $vacancy = Vacancy::factory()->published()->create(['salary_min' => 1200, 'salary_max' => 1800, 'deadline' => now()->addDays(20)]);

    $schema = schemaOfType($this->get(localized_route('jobs.show', [$vacancy]))->assertOk()->getContent(), 'JobPosting');

    expect($schema['title'])->toBe($vacancy->title)
        ->and($schema)->toHaveKeys(['description', 'datePosted', 'validThrough', 'hiringOrganization', 'jobLocation', 'employmentType'])
        ->and($schema['baseSalary']['value']['minValue'])->toBe(1200)
        ->and($schema['baseSalary']['value']['maxValue'])->toBe(1800)
        ->and($schema['validThrough'])->toStartWith(now()->addDays(20)->toDateString());
});

it('marks up properties, events and news', function () {
    $property = Property::factory()->published()->create(['price' => 95000, 'currency' => 'USD', 'area_m2' => 80]);
    $event = Event::factory()->published()->create(['starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'is_all_day' => false]);
    $article = Article::factory()->published()->create();

    $listing = schemaOfType($this->get(localized_route('real-estate.show', [$property]))->getContent(), 'RealEstateListing');
    expect($listing['offers']['priceCurrency'])->toBe('USD')
        ->and($listing['about']['floorSize']['unitCode'])->toBe('MTK');

    expect(schemaOfType($this->get(localized_route('events.show', [$event]))->getContent(), 'Event'))
        ->toHaveKeys(['startDate', 'location', 'eventStatus', 'offers']);

    expect(schemaOfType($this->get(localized_route('news.show', [$article]))->getContent(), 'NewsArticle'))
        ->toHaveKeys(['headline', 'datePublished', 'publisher']);
});

it('adds the organization and the site search box to the homepage', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(schemaOfType($html, 'Organization')['name'])->toBe('infokobuleti')
        ->and(schemaOfType($html, 'WebSite')['potentialAction']['@type'])->toBe('SearchAction');
});

it('uses the title and description templates set in the admin', function () {
    $property = Property::factory()->published()->create(['price' => 90, 'currency' => 'GEL']);
    app(Settings::class)->put([
        'seo.templates.property.title.ka' => '{title} — {price} | {site}',
        'seo.templates.property.description.en' => 'See {title} in Kobuleti.',
    ]);

    $this->get(localized_route('real-estate.show', [$property]))
        ->assertSee('<title>'.$property->title.' — ₾90 | infokobuleti</title>', false);

    app()->setLocale('en');
    $this->get(localized_route('real-estate.show', [$property], 'en'))
        ->assertSee('<meta name="description" content="See '.e($property->getTranslation('title', 'en')).' in Kobuleti.">', false);
});

it('keeps previews, account pages and search out of the index', function () {
    $draft = Property::factory()->create(['status' => ListingStatus::Pending]);

    $this->get('/search?q=test')->assertSee('noindex', false);
    $this->get('/login')->assertSee('noindex', false);

    $this->actingAs($draft->user)->get(localized_route('real-estate.show', [$draft]))
        ->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false)->assertDontSee('rel="canonical"', false);
});

it('closes robots.txt outside production and opens it in production', function () {
    $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);

    app()->detectEnvironment(fn () => 'production');

    $this->get('/robots.txt')->assertOk()
        ->assertSee('Disallow: /account', false)
        ->assertSee('Disallow: /en/admin', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
        ->assertDontSee("Disallow: /\n", false);
});

it('writes one sitemap per module with language alternates', function () {
    $published = Property::factory()->published()->create();
    $draft = Property::factory()->create(['status' => ListingStatus::Draft]);
    $path = storage_path('framework/testing/sitemap');

    $this->artisan('sitemap:generate', ['--path' => $path])->assertSuccessful();

    $estate = File::get($path.'/sitemaps/real-estate.xml');
    expect(File::get($path.'/sitemap.xml'))->toContain(url('/sitemaps/real-estate.xml'))->toContain(url('/sitemaps/pages.xml'))
        ->and($estate)->toContain(localized_route('real-estate.show', [$published]))
        ->toContain('hreflang="en" href="'.localized_route('real-estate.show', [$published], 'en').'"')
        ->not->toContain('/'.$draft->slug.'<');

    File::deleteDirectory($path);
});

it('draws a share picture for public pages only', function () {
    Storage::fake('public');
    $hotel = Property::factory()->published()->create();
    $draft = Property::factory()->create(['status' => ListingStatus::Pending]);

    $response = $this->get("/og/property/{$hotel->id}-ka.jpg")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $size = getimagesizefromstring((string) file_get_contents($response->baseResponse->getFile()->getPathname()));

    expect([$size[0], $size[1]])->toBe([1200, 630]);

    $this->get("/og/property/{$draft->id}-ka.jpg")->assertNotFound();
    $this->get("/og/property/{$hotel->id}-de.jpg")->assertNotFound();
});
