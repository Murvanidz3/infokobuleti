<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Listings\PublicCache;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;

/**
 * Site settings from the admin, cached; anything not set falls back to the config files.
 */
class Settings
{
    private const string CACHE_KEY = 'settings.all';

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function __construct(private Cache $cache) {}

    /**
     * @return array<string, mixed> flat, keyed "group.name"
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $cached = $this->cache->get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $this->values = $cached;
        }

        try {
            $values = Setting::query()->pluck('value', 'key')->all();
        } catch (QueryException) {
            // Before the settings table exists (fresh install, tests): nothing is cached.
            return [];
        }

        $this->cache->forever(self::CACHE_KEY, $values);

        return $this->values = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return $value ?? $default;
    }

    /**
     * Nested array of everything under a prefix: group("contacts") => ["phone" => ...].
     *
     * @return array<string, mixed>
     */
    public function group(string $prefix): array
    {
        $values = [];

        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, $prefix.'.')) {
                Arr::set($values, substr($key, strlen($prefix) + 1), $value);
            }
        }

        return $values;
    }

    /**
     * Save flat "group.name" => value pairs. Null removes the setting.
     *
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                Setting::query()->whereKey($key)->delete();

                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->values = null;
        $this->cache->forget(self::CACHE_KEY);

        // Contacts, SEO texts and other settings are part of every cached page.
        PublicCache::bump();
    }

    /**
     * Days before a published item of this module expires (admin setting, else config/listings.php).
     */
    public function expiryDays(string $morphAlias): ?int
    {
        $days = $this->get("listings.expiry_days.{$morphAlias}", config("listings.expiry_days.{$morphAlias}"));

        return is_numeric($days) ? (int) $days : null;
    }

    public function maintenanceMode(): bool
    {
        return (bool) $this->get('site.maintenance', false);
    }
}
