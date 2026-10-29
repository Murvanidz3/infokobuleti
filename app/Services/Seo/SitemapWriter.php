<?php

namespace App\Services\Seo;

use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Page;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;
use App\Support\Listings\ListingQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use XMLWriter;

/**
 * XML sitemaps, one file per module (split every 10 000 items), with the ka/en/ru versions of each
 * page as hreflang alternates, and a sitemap index at /sitemap.xml.
 */
final class SitemapWriter
{
    public const int PER_FILE = 10000;

    /** List pages, the homepage and contact. */
    private const array STATIC_ROUTES = ['home', 'hotels.index', 'real-estate.index', 'classifieds.index', 'jobs.index', 'guide.index', 'events.index', 'news.index', 'contact'];

    /**
     * Writes every file into $directory (the public folder) and returns the number of URLs per file.
     *
     * @return array<string, int>
     */
    public function write(string $directory): array
    {
        $folder = $directory.'/sitemaps';
        File::ensureDirectoryExists($folder);
        File::cleanDirectory($folder);

        $counts = [];
        $counts += $this->writeFile($folder, 'pages', $this->staticEntries());

        $counts += $this->module('hotels', 'hotels.show', Hotel::query(), $folder);
        $counts += $this->module('real-estate', 'real-estate.show', Property::query(), $folder);
        $counts += $this->module('classifieds', 'classifieds.show', Classified::query(), $folder);
        $counts += $this->module('jobs', 'jobs.show', Vacancy::query(), $folder);
        $counts += $this->module('guide', 'guide.show', Place::query(), $folder);
        $counts += $this->module('events', 'events.show', Event::query(), $folder);
        $counts += $this->module('news', 'news.show', Article::query(), $folder);

        $businesses = BusinessProfile::query()->select(['id', 'slug', 'updated_at'])->orderBy('id')->get();
        $counts += $this->writeFile($folder, 'business', $businesses->map(fn (BusinessProfile $profile): array => $this->entry('business.show', [$profile], $profile->updated_at))->all());

        $this->writeIndex($directory, array_keys($counts));

        return $counts;
    }

    /**
     * Published items of one module, PER_FILE per file ("hotels.xml", "hotels-2.xml", ...).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return array<string, int>
     */
    private function module(string $module, string $route, Builder $query, string $folder): array
    {
        $counts = [];
        $chunk = 0;

        ListingQuery::public($query)->select(['id', 'slug', 'updated_at'])->orderBy('id')
            ->chunkById(self::PER_FILE, function ($records) use (&$counts, &$chunk, $folder, $module, $route): void {
                $chunk++;
                $entries = $records->map(fn (Model $record): array => $this->entry($route, [$record], $record->getAttribute('updated_at')))->values()->all();
                $counts += $this->writeFile($folder, $chunk === 1 ? $module : "{$module}-{$chunk}", $entries);
            });

        return $counts === [] ? $this->writeFile($folder, $module, []) : $counts;
    }

    /**
     * @return list<array{urls: array<string, string>, lastmod: ?string}>
     */
    private function staticEntries(): array
    {
        $entries = array_map(fn (string $route): array => $this->entry($route, [], null), self::STATIC_ROUTES);

        foreach (PropertyType::cases() as $type) {
            $entries[] = $this->entry('real-estate.type', ['type' => $type->value], null);

            foreach (PropertyDeal::cases() as $deal) {
                $entries[] = $this->entry('real-estate.browse', ['type' => $type->value, 'deal' => $deal->value], null);
            }
        }

        foreach (Page::query()->where('is_published', true)->get(['id', 'slug', 'updated_at']) as $page) {
            $entries[] = $this->entry('pages.show', ['page' => $page->slug], $page->updated_at);
        }

        return $entries;
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     * @return array{urls: array<string, string>, lastmod: ?string}
     */
    private function entry(string $route, array $parameters, ?CarbonInterface $updated): array
    {
        $urls = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $urls[$locale] = localized_route($route, $parameters, $locale);
        }

        return ['urls' => $urls, 'lastmod' => $updated?->toAtomString()];
    }

    /**
     * One <url> per language version, each listing all versions as alternates.
     *
     * @param  list<array{urls: array<string, string>, lastmod: ?string}>  $entries
     * @return array<string, int>
     */
    private function writeFile(string $folder, string $name, array $entries): array
    {
        $xml = new XMLWriter;
        $xml->openUri("{$folder}/{$name}.xml");
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $count = 0;
        $default = config('locales.default');

        foreach ($entries as $entry) {
            foreach ($entry['urls'] as $url) {
                $xml->startElement('url');
                $xml->writeElement('loc', $url);

                if ($entry['lastmod'] !== null) {
                    $xml->writeElement('lastmod', $entry['lastmod']);
                }

                foreach ($entry['urls'] as $locale => $alternate) {
                    $this->alternate($xml, (string) config("locales.supported.{$locale}.hreflang", $locale), $alternate);
                }

                $this->alternate($xml, 'x-default', $entry['urls'][$default] ?? reset($entry['urls']));
                $xml->endElement();
                $count++;
            }
        }

        $xml->endElement();
        $xml->endDocument();
        $xml->flush();

        return ["{$name}.xml" => $count];
    }

    private function alternate(XMLWriter $xml, string $hreflang, string $url): void
    {
        $xml->startElement('xhtml:link');
        $xml->writeAttribute('rel', 'alternate');
        $xml->writeAttribute('hreflang', $hreflang);
        $xml->writeAttribute('href', $url);
        $xml->endElement();
    }

    /**
     * @param  list<string>  $files
     */
    private function writeIndex(string $directory, array $files): void
    {
        $xml = new XMLWriter;
        $xml->openUri($directory.'/sitemap.xml');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('sitemapindex');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $now = now()->toAtomString();

        foreach ($files as $file) {
            $xml->startElement('sitemap');
            $xml->writeElement('loc', url('/sitemaps/'.$file));
            $xml->writeElement('lastmod', $now);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }
}
