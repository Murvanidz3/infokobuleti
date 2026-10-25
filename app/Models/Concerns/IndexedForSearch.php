<?php

namespace App\Models\Concerns;

use App\Support\Database\ListingSchema;
use Laravel\Scout\Searchable;

/**
 * Scout indexing for public listings: only published items are indexed (Meilisearch), and the
 * PostgreSQL engine reuses the same full-text expression as the table's GIN index.
 *
 * @property-read list<string> $searchColumns
 */
trait IndexedForSearch
{
    use Searchable;

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    public static function searchVectorSql(): string
    {
        return ListingSchema::searchVector(static::searchColumns());
    }

    public static function searchTitleSql(): string
    {
        $table = static::query()->getModel()->getTable();

        return '('.implode(" || ' ' || ", array_map(fn (string $locale): string => "coalesce({$table}.title->>'{$locale}', '')", ListingSchema::LOCALES)).')';
    }

    /**
     * Columns of the full-text index (see the module's migration).
     *
     * @return list<string>
     */
    public static function searchColumns(): array
    {
        return ['title', 'description'];
    }

    /**
     * What Meilisearch stores: every translation, filterable fields and coordinates.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $document = ['id' => $this->getKey()];

        foreach (static::searchColumns() as $column) {
            foreach (ListingSchema::LOCALES as $locale) {
                $value = $this->getTranslation($column, $locale, false);
                $document["{$column}_{$locale}"] = is_string($value) ? mb_substr(strip_tags($value), 0, 2000) : null;
            }
        }

        $document['status'] = $this->status->value;
        $document['published_at'] = $this->published_at?->getTimestamp();
        $document['promotion_level'] = (int) ($this->getAttributes()['promotion_level'] ?? 0);

        if ($this->getAttribute('lat') !== null && $this->getAttribute('lng') !== null) {
            $document['_geo'] = ['lat' => (float) $this->getAttribute('lat'), 'lng' => (float) $this->getAttribute('lng')];
        }

        return array_merge($document, $this->searchableFilters());
    }

    /**
     * Module-specific filterable values for Meilisearch.
     *
     * @return array<string, mixed>
     */
    protected function searchableFilters(): array
    {
        return [];
    }
}
