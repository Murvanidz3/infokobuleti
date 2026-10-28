<?php

namespace App\Search;

use App\Services\Settings;

/**
 * Synonym groups edited in the admin (Settings → search), one group per line, comma separated ("sea, beach" plus their Georgian and Russian words).
 */
class SearchSynonyms
{
    public function __construct(private Settings $settings) {}

    /**
     * @return list<list<string>>
     */
    public function groups(): array
    {
        $lines = preg_split('/\R/u', (string) $this->settings->get('search.synonyms', '')) ?: [];
        $groups = [];

        foreach ($lines as $line) {
            $terms = array_values(array_unique(array_filter(array_map(
                fn (string $term): string => mb_strtolower(trim($term)),
                explode(',', $line),
            ), fn (string $term): bool => $term !== '')));

            if (count($terms) > 1) {
                $groups[] = $terms;
            }
        }

        return $groups;
    }

    /**
     * The word itself plus every term sharing a group with it.
     *
     * @return list<string>
     */
    public function expand(string $word): array
    {
        $terms = [$word];

        foreach ($this->groups() as $group) {
            if (in_array($word, $group, true)) {
                $terms = [...$terms, ...$group];
            }
        }

        return array_values(array_unique(array_filter(array_map(
            fn (string $term): string => trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $term) ?? ''),
            $terms,
        ))));
    }

    /**
     * Meilisearch format: every term maps to the others of its group.
     *
     * @return array<string, list<string>>
     */
    public function forMeilisearch(): array
    {
        $map = [];

        foreach ($this->groups() as $group) {
            foreach ($group as $term) {
                $map[$term] = array_values(array_unique([...($map[$term] ?? []), ...array_diff($group, [$term])]));
            }
        }

        return $map;
    }
}
