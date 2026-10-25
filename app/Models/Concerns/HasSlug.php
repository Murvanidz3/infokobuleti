<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * One transliterated slug per record for every locale ("sastumro-sanapiro"), generated once on
 * create and never changed afterwards so links stay stable. Requires HasTranslations.
 *
 * @property string $slug
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (Model $model): void {
            /** @var static $model */
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    public function generateUniqueSlug(): string
    {
        $base = Str::limit(Str::slug($this->slugSource()), 80, '');
        $base = rtrim($base, '-') ?: Str::lower(Str::random(8));

        $slug = $base;
        $suffix = 2;

        while ($this->slugQuery()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Text the slug is built from: the Georgian title (or name), falling back to English.
     */
    protected function slugSource(): string
    {
        $field = array_key_exists('title', $this->attributes) ? 'title' : 'name';

        if ($this->isTranslatableAttribute($field)) {
            return (string) $this->getTranslation($field, config('locales.default'));
        }

        return (string) $this->getAttribute($field);
    }

    /**
     * Rows the slug must be unique among. Includes soft-deleted rows so a restore never collides.
     *
     * @return Builder<static>
     */
    protected function slugQuery(): Builder
    {
        $query = static::query();

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }
}
