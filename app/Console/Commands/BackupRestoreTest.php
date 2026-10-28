<?php

namespace App\Console\Commands;

use App\Services\Monitoring\Alerts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Proves the newest backup can be restored: unpacks its database dump into a scratch database
 * (never the live one) and compares row counts of the main tables with the live database.
 */
#[Signature('backup:restore-test {--database= : Scratch database (default: monitoring.restore_database)}')]
#[Description('Restore the newest database backup into a scratch database and compare row counts')]
class BackupRestoreTest extends Command
{
    public const string CACHE_KEY = 'monitoring:restore-test';

    /** Tables whose row counts must survive the round trip. */
    private const array TABLES = ['users', 'hotels', 'properties', 'classifieds', 'vacancies', 'orders', 'settings'];

    public function handle(Alerts $alerts): int
    {
        $database = (string) ($this->option('database') ?: config('monitoring.restore_database'));
        $live = (string) config('database.connections.pgsql.database');

        if ($database === '' || $database === $live) {
            $this->error('Refusing to restore into the live database.');

            return self::FAILURE;
        }

        $workdir = storage_path('app/restore-test');

        try {
            $archive = $this->newestArchive();
            $dump = $this->extractDump($archive, $workdir);
            $this->restore($dump, $database);
            $report = $this->compare($database);
        } catch (RuntimeException $exception) {
            File::deleteDirectory($workdir);
            $alerts->raise('backup-restore', __('monitoring.restore.failed_title'), $exception->getMessage());
            Cache::forever(self::CACHE_KEY, ['at' => now()->toIso8601String(), 'ok' => false, 'detail' => $exception->getMessage()]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        File::deleteDirectory($workdir);
        $failed = array_filter($report, fn (array $row): bool => ! $row['ok']);

        $this->table(['Table', 'Live', 'Restored', ''], array_map(fn (string $table, array $row): array => [$table, $row['live'], $row['restored'], $row['ok'] ? 'ok' : 'MISMATCH'], array_keys($report), $report));

        $detail = basename($archive).': '.implode(', ', array_map(fn (string $table, array $row): string => "{$table} {$row['restored']}/{$row['live']}", array_keys($report), $report));
        Cache::forever(self::CACHE_KEY, ['at' => now()->toIso8601String(), 'ok' => $failed === [], 'detail' => $detail]);

        if ($failed !== []) {
            $alerts->raise('backup-restore', __('monitoring.restore.failed_title'), $detail);

            return self::FAILURE;
        }

        Log::channel('alerts')->info('Backup restore test passed — '.$detail);
        $alerts->resolve('backup-restore');
        $this->info('Restore test passed.');

        return self::SUCCESS;
    }

    private function newestArchive(): string
    {
        $disk = Storage::disk('backups');
        $newest = collect($disk->allFiles())
            ->filter(fn (string $file): bool => str_ends_with($file, '.zip'))
            ->sortByDesc(fn (string $file): int => $disk->lastModified($file))
            ->first();

        if ($newest === null) {
            throw new RuntimeException('No backup archive found.');
        }

        return $disk->path($newest);
    }

    private function extractDump(string $archive, string $workdir): string
    {
        File::deleteDirectory($workdir);
        File::ensureDirectoryExists($workdir);

        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new RuntimeException('The backup archive cannot be opened: '.basename($archive));
        }

        if (filled($password = config('backup.backup.password'))) {
            $zip->setPassword((string) $password);
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);

            if (str_starts_with($name, 'db-dumps/') && ! $zip->extractTo($workdir, $name)) {
                $zip->close();

                throw new RuntimeException('The database dump cannot be extracted (is BACKUP_ARCHIVE_PASSWORD right?): '.basename($archive));
            }
        }

        $zip->close();

        $dump = collect(File::allFiles($workdir))->map->getPathname()->first(fn (string $path): bool => (bool) preg_match('/\.sql(\.gz)?$/', $path));

        if ($dump === null) {
            throw new RuntimeException('The backup has no database dump: '.basename($archive));
        }

        if (str_ends_with($dump, '.gz')) {
            $plain = substr($dump, 0, -3);
            file_put_contents($plain, gzdecode((string) file_get_contents($dump)) ?: '');
            $dump = $plain;
        }

        return $dump;
    }

    /**
     * Empties the scratch database (tables, views, sequences we own; extensions stay) and loads
     * the dump into it.
     */
    private function restore(string $dump, string $database): void
    {
        $connection = $this->connection($database);

        $connection->statement(<<<'SQL'
            DO $$
            DECLARE item record;
            BEGIN
                FOR item IN SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewowner = current_user LOOP
                    EXECUTE format('DROP VIEW IF EXISTS public.%I CASCADE', item.viewname);
                END LOOP;
                FOR item IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tableowner = current_user LOOP
                    EXECUTE format('DROP TABLE IF EXISTS public.%I CASCADE', item.tablename);
                END LOOP;
                FOR item IN SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = 'public' LOOP
                    EXECUTE format('DROP SEQUENCE IF EXISTS public.%I CASCADE', item.sequence_name);
                END LOOP;
            END $$;
            SQL);
        DB::purge('restore_test');

        $config = config('database.connections.pgsql');
        $result = Process::timeout(1800)
            ->env(['PGPASSWORD' => (string) $config['password']])
            ->run(['psql', '-q', '-h', (string) $config['host'], '-p', (string) $config['port'], '-U', (string) $config['username'], '-d', $database, '-f', $dump]);

        // Statements a non-superuser may not run (owners, extension comments) only print errors;
        // psql fails as a whole only when it cannot connect or read the file. Row counts decide.
        if ($result->failed()) {
            throw new RuntimeException('psql failed: '.trim($result->errorOutput()));
        }
    }

    /**
     * @return array<string, array{live: int, restored: int, ok: bool}>
     */
    private function compare(string $database): array
    {
        $restored = $this->connection($database);
        $report = [];

        foreach (self::TABLES as $table) {
            $live = (int) DB::table($table)->count();

            try {
                $count = (int) $restored->table($table)->count();
            } catch (Throwable) {
                $count = -1;
            }

            // The backup is a few hours old: allow growth since then, never an empty table.
            $report[$table] = ['live' => $live, 'restored' => $count, 'ok' => $count >= 0 && ($live === 0 || ($count > 0 && $count >= (int) floor($live * 0.8)))];
        }

        DB::purge('restore_test');

        return $report;
    }

    private function connection(string $database): Connection
    {
        config(['database.connections.restore_test' => array_merge(config('database.connections.pgsql'), ['database' => $database])]);

        return DB::connection('restore_test');
    }
}
