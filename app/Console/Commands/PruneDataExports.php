<?php

namespace App\Console\Commands;

use App\Actions\Privacy\ExportUserData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('privacy:prune-exports')]
#[Description('Delete data export files older than their download link')]
class PruneDataExports extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('local');
        $limit = now()->subDays(ExportUserData::DAYS)->getTimestamp();
        $deleted = 0;

        foreach ($disk->files(ExportUserData::DIRECTORY) as $file) {
            if ($disk->lastModified($file) < $limit) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} old export(s).");

        return self::SUCCESS;
    }
}
