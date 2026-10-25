<?php

namespace App\Search;

/**
 * A model the PostgreSQL search engine can query: it exposes the SQL of its full-text vector
 * (identical to the indexed expression) and of its concatenated titles for fuzzy matching.
 */
interface SearchableListing
{
    public static function searchVectorSql(): string;

    public static function searchTitleSql(): string;
}
