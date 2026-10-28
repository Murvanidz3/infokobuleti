<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * A 301/302 redirect managed in the admin: old address (path) to a new page.
 *
 * @property int $id
 * @property string $from_path
 * @property string $to_url
 * @property int $status_code
 * @property int $hits
 * @property Carbon|null $last_hit_at
 */
#[Fillable(['from_path', 'to_url', 'status_code', 'created_by'])]
class Redirect extends Model
{
    public const string CACHE_KEY = 'redirects.map';

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime', 'status_code' => 'integer', 'hits' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = self::normalize($redirect->from_path);
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * "/Old-Page/?x=1" and "old-page" both become "/old-page"; full URLs keep only their path.
     */
    public static function normalize(string $path): string
    {
        $path = trim($path);

        if (preg_match('#^https?://#i', $path) === 1) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        $path = '/'.trim(rawurldecode(strtok($path, '?#') ?: ''), '/');

        return mb_strtolower($path);
    }

    /**
     * Every redirect by its path, cached until one changes.
     *
     * @return array<string, array{id: int, to: string, code: int}>
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => self::query()
            ->get(['id', 'from_path', 'to_url', 'status_code'])
            ->mapWithKeys(fn (Redirect $redirect): array => [$redirect->from_path => ['id' => $redirect->id, 'to' => $redirect->to_url, 'code' => $redirect->status_code]])
            ->all());
    }
}
