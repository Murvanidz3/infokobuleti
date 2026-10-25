<?php

namespace App\Search;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\DatabaseEngine;

/**
 * Scout engine that searches the listing tables directly, so local development and tests need no
 * Meilisearch. Matches whole words and prefixes through the full-text index, and tolerates typos
 * through pg_trgm word similarity on titles. Synonyms come from the admin settings.
 */
class PostgresSearchEngine extends DatabaseEngine
{
    /** Minimum pg_trgm word similarity for a title to count as a fuzzy match. */
    public const float SIMILARITY = 0.3;

    public function __construct(private SearchSynonyms $synonyms)
    {
        parent::__construct();
    }

    /**
     * @param  Builder<Model>  $builder
     * @return EloquentBuilder<Model>
     */
    protected function buildSearchQuery(Builder $builder)
    {
        $model = $builder->model;
        $query = $model->newQuery();
        $search = trim((string) $builder->query);

        if ($search !== '' && $model instanceof SearchableListing) {
            $this->constrain($query, $model, $search);
        }

        $query = $this->addAdditionalConstraints($builder, $query->take($builder->limit));

        return $this->constrainForSoftDeletes($builder, $query);
    }

    /**
     * @param  Builder<Model>  $builder
     */
    protected function shouldOrderByRelevance(Builder $builder): bool
    {
        return false;
    }

    /**
     * Non-empty so the parent does not add its own "order by id desc" before ours.
     *
     * @param  Builder<Model>  $builder
     * @return list<string>
     */
    protected function getFullTextColumns(Builder $builder)
    {
        return ['search'];
    }

    /**
     * @param  EloquentBuilder<Model>  $query
     */
    private function constrain(EloquentBuilder $query, SearchableListing $model, string $search): void
    {
        $vector = $model::searchVectorSql();
        $titles = $model::searchTitleSql();
        $tsQuery = $this->tsQuery($search);
        $plain = mb_strtolower(mb_substr($search, 0, 100));

        $query->where(function (EloquentBuilder $query) use ($vector, $titles, $tsQuery, $plain): void {
            if ($tsQuery !== null) {
                $query->whereRaw("{$vector} @@ to_tsquery('simple', ?)", [$tsQuery]);
            }

            $query->orWhereRaw("word_similarity(?, lower({$titles})) >= ?", [$plain, self::SIMILARITY]);
        });

        $rank = $tsQuery !== null ? "ts_rank({$vector}, to_tsquery('simple', ?))" : '0';
        $bindings = $tsQuery !== null ? [$tsQuery, $plain] : [$plain];

        $query->orderByRaw("greatest({$rank}, word_similarity(?, lower({$titles}))) desc", $bindings);
    }

    /**
     * "sea hot" → "(sea:* | beach:*) & hot:*": every word must match, as a prefix, or one of its synonyms.
     */
    private function tsQuery(string $search): ?string
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($search), $matches);
        $words = array_slice(array_unique($matches[0]), 0, 8);

        if ($words === []) {
            return null;
        }

        $groups = array_map(function (string $word): string {
            $terms = array_map(
                fn (string $term): string => implode(' & ', array_map(fn (string $part): string => $part.':*', preg_split('/\s+/u', $term) ?: [$term])),
                $this->synonyms->expand($word),
            );

            return count($terms) === 1 ? $terms[0] : '('.implode(' | ', array_map(fn (string $term): string => "({$term})", $terms)).')';
        }, $words);

        return implode(' & ', $groups);
    }
}
