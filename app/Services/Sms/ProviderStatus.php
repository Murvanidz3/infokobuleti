<?php

namespace App\Services\Sms;

use App\Contracts\SmsProviderAccount;
use Illuminate\Contracts\Cache\Repository as Cache;
use Throwable;

/**
 * Balance and sender names of the configured SMS provider for the admin, cached for two
 * minutes so pages do not call the provider on every render.
 *
 * @phpstan-type Status array{driver: string, balance: int|null, brands: list<array{id: int, name: string, authorized: bool, status: int}>, error: string|null, checked_at: string}
 */
class ProviderStatus
{
    private const string CACHE_KEY = 'sms:provider-status';

    public function __construct(private SmsManager $sms, private Cache $cache) {}

    /**
     * @return Status
     */
    public function get(): array
    {
        $cached = $this->cache->get(self::CACHE_KEY);

        if (is_array($cached) && ($cached['driver'] ?? null) === config('sms.default')) {
            return $cached;
        }

        $status = $this->fetch();
        $this->cache->put(self::CACHE_KEY, $status, now()->addMinutes(2));

        return $status;
    }

    public function forget(): void
    {
        $this->cache->forget(self::CACHE_KEY);

        try {
            $gateway = $this->sms->driver();

            if ($gateway instanceof UbillSmsGateway) {
                $gateway->forgetBrand();
            }
        } catch (Throwable) {
            // Not configured.
        }
    }

    public function balance(): ?int
    {
        return $this->get()['balance'];
    }

    /**
     * @return Status
     */
    private function fetch(): array
    {
        $status = ['driver' => (string) config('sms.default'), 'balance' => null, 'brands' => [], 'error' => null, 'checked_at' => now()->toIso8601String()];

        try {
            $gateway = $this->sms->driver();

            if ($gateway instanceof SmsProviderAccount) {
                $status['balance'] = $gateway->balance();
                $status['brands'] = $gateway->brands();
            }
        } catch (Throwable $exception) {
            $status['error'] = $exception->getMessage();
        }

        return $status;
    }
}
