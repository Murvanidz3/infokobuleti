<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapWriter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sitemap:generate {--path= : Folder to write into (default: public)}')]
#[Description('Write the XML sitemaps (one per module) and the sitemap index')]
class GenerateSitemap extends Command
{
    public function handle(SitemapWriter $writer): int
    {
        $counts = $writer->write($this->option('path') ?: public_path());

        foreach ($counts as $file => $count) {
            $this->line("{$file}: {$count} URLs");
        }

        return self::SUCCESS;
    }
}
