<?php

namespace App\Console\Commands;

use App\Search\SearchSynonyms;
use App\Search\SiteSearch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Meilisearch\Client;

#[Signature('search:sync {--import : Re-import every published listing}')]
#[Description('Push index settings and admin-edited synonyms to Meilisearch (optionally re-import listings)')]
class SyncSearch extends Command
{
    public function handle(SearchSynonyms $synonyms): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->info('Search runs on PostgreSQL ('.config('scout.driver').'): nothing to sync.');

            return self::SUCCESS;
        }

        $this->call('scout:sync-index-settings');

        $client = app(Client::class);
        $map = $synonyms->forMeilisearch();

        foreach (SiteSearch::MODULES as [$class]) {
            $index = (new $class)->searchableAs();
            $client->index($index)->updateSynonyms($map === [] ? new \stdClass : $map);
            $this->line("Synonyms → {$index} (".count($map).')');

            if ($this->option('import')) {
                $this->call('scout:import', ['model' => $class]);
            }
        }

        return self::SUCCESS;
    }
}
