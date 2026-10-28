<?php

namespace App\Models;

use App\Enums\CategoryModule;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsStaffActivity;
use App\Support\AttributeSchema;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['parent_id', 'module', 'name', 'slug', 'icon', 'sort', 'attribute_schema', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug, HasTranslations, LogsStaffActivity;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'module' => CategoryModule::class,
            'attribute_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    /**
     * @return HasMany<Classified, $this>
     */
    public function classifieds(): HasMany
    {
        return $this->hasMany(Classified::class);
    }

    /**
     * @return HasMany<Vacancy, $this>
     */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * The category's own schema, or the nearest ancestor's when it has none.
     */
    public function schema(): AttributeSchema
    {
        $category = $this;

        while ($category !== null && blank($category->attribute_schema)) {
            $category = $category->parent;
        }

        return new AttributeSchema($category->attribute_schema ?? []);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForModule(Builder $query, CategoryModule $module): void
    {
        $query->where('module', $module);
    }

    /**
     * @return Builder<static>
     */
    protected function slugQuery(): Builder
    {
        return static::query()->where('module', $this->module);
    }
}
