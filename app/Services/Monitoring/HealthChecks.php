<?php

namespace App\Services\Monitoring;

use App\Services\Sms\SmsManager;
use App\Services\Sms\UbillSmsGateway;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/**
 * Server and application health: disk, memory, queue worker, failed jobs, backlog, last backup and
 * the SSL certificate and the SMS balance. monitor:check runs these every five minutes, raises alerts for failures and
 * keeps the last result for the admin dashboard.
 *
 * @phpstan-type Check array{key: string, ok: bool|null, value: string, detail: string}
 */
class HealthChecks
{
    public const string CACHE_KEY = 'monitoring:last';

    public function __construct(private Cache $cache) {}

    /**
     * @return list<Check>
     */
    public function run(): array
    {
        $checks = [
            $this->disk(),
            $this->memory(),
            $this->horizon(),
            $this->failedJobs(),
            $this->backlog(),
            $this->backup(),
            $this->certificate(),
            $this->smsBalance(),
        ];

        $this->cache->forever(self::CACHE_KEY, ['at' => now()->toIso8601String(), 'checks' => $checks]);

        return $checks;
    }

    /**
     * @return array{at: ?Carbon, checks: list<Check>}
     */
    public function last(): array
    {
        $last = $this->cache->get(self::CACHE_KEY);

        return is_array($last)
            ? ['at' => Carbon::parse($last['at']), 'checks' => $last['checks']]
            : ['at' => null, 'checks' => []];
    }

    /**
     * @return Check
     */
    private function disk(): array
    {
        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());

        if (! $total || $free === false) {
            return $this->result('disk', null, '—');
        }

        $percent = (int) round($free / $total * 100);

        return $this->result('disk', $percent >= (int) config('monitoring.disk_free_percent'), $percent.'%', __('monitoring.detail.disk', ['free' => $this->bytes((float) $free), 'total' => $this->bytes((float) $total)]));
    }

    /**
     * @return Check
     */
    private function memory(): array
    {
        $info = @file_get_contents('/proc/meminfo');

        if (! is_string($info) || ! preg_match('/MemTotal:\s+(\d+)/', $info, $total) || ! preg_match('/MemAvailable:\s+(\d+)/', $info, $available)) {
            return $this->result('memory', null, '—');
        }

        $percent = (int) round((int) $available[1] / max(1, (int) $total[1]) * 100);

        return $this->result('memory', $percent >= (int) config('monitoring.memory_free_percent'), $percent.'%', __('monitoring.detail.memory', ['free' => $this->bytes((int) $available[1] * 1024)]));
    }

    /**
     * @return Check
     */
    private function horizon(): array
    {
        if (config('queue.default') !== 'redis') {
            return $this->result('horizon', null, (string) config('queue.default'));
        }

        try {
            $running = app(MasterSupervisorRepository::class)->all() !== [];
        } catch (Throwable) {
            return $this->result('horizon', false, '—', __('monitoring.detail.redis_down'));
        }

        return $this->result('horizon', $running, $running ? __('monitoring.value.running') : __('monitoring.value.stopped'));
    }

    /**
     * @return Check
     */
    private function failedJobs(): array
    {
        $count = DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count();

        return $this->result('failed_jobs', $count === 0, (string) $count, __('monitoring.detail.failed_jobs'));
    }

    /**
     * @return Check
     */
    private function backlog(): array
    {
        if (config('queue.default') === 'sync') {
            return $this->result('backlog', null, '0');
        }

        try {
            $size = Queue::size();
        } catch (Throwable) {
            return $this->result('backlog', null, '—');
        }

        return $this->result('backlog', $size <= (int) config('monitoring.queue_backlog'), (string) $size);
    }

    /**
     * Age of the newest backup archive on the backups disk.
     *
     * @return Check
     */
    private function backup(): array
    {
        if (! config('backup.backup.name')) {
            return $this->result('backup', null, '—');
        }

        $disk = Storage::disk('backups');
        $newest = collect($disk->allFiles())->filter(fn (string $file): bool => str_ends_with($file, '.zip'))->map(fn (string $file): int => $disk->lastModified($file))->max();

        if ($newest === null) {
            return $this->result('backup', false, __('monitoring.value.none'));
        }

        $hours = (int) floor((time() - $newest) / 3600);

        return $this->result('backup', $hours < (int) config('monitoring.backup_max_age_hours'), __('monitoring.value.hours_ago', ['hours' => $hours]));
    }

    /**
     * Days until the site's HTTPS certificate expires.
     *
     * @return Check
     */
    private function certificate(): array
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! str_starts_with((string) config('app.url'), 'https://') || ! is_string($host) || app()->environment('local', 'testing')) {
            return $this->result('ssl', null, '—');
        }

        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $client = @stream_socket_client("ssl://{$host}:443", $errno, $error, 5, STREAM_CLIENT_CONNECT, $context);

        if ($client === false) {
            return $this->result('ssl', false, '—', $error);
        }

        $certificate = openssl_x509_parse(stream_context_get_params($client)['options']['ssl']['peer_certificate'] ?? '');
        fclose($client);
        $days = is_array($certificate) ? (int) floor(($certificate['validTo_time_t'] - time()) / 86400) : -1;

        return $this->result('ssl', $days >= (int) config('monitoring.ssl_min_days'), __('monitoring.value.days', ['days' => $days]));
    }

    /**
     * SMS left on the UBill balance (sign-in codes stop when it runs out).
     *
     * @return Check
     */
    private function smsBalance(): array
    {
        if (config('sms.default') !== 'ubill') {
            return $this->result('sms', null, (string) config('sms.default'));
        }

        try {
            $gateway = app(SmsManager::class)->driver('ubill');
            $balance = $gateway instanceof UbillSmsGateway ? $gateway->balance() : null;
        } catch (Throwable $exception) {
            return $this->result('sms', false, '—', $exception->getMessage());
        }

        if ($balance === null) {
            return $this->result('sms', null, '—');
        }

        return $this->result('sms', $balance >= (int) config('sms.drivers.ubill.min_balance'), __('monitoring.value.sms', ['count' => $balance]));
    }

    /**
     * @return Check
     */
    private function result(string $key, ?bool $ok, string $value, string $detail = ''): array
    {
        return ['key' => $key, 'ok' => $ok, 'value' => $value, 'detail' => $detail];
    }

    private function bytes(float $bytes): string
    {
        return $bytes >= 1073741824 ? round($bytes / 1073741824, 1).' GB' : round($bytes / 1048576).' MB';
    }
}
