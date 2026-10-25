<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A news article. user_id is the author.
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Article extends Model implements Listing
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasMediaGallery, HasSlug, HasStatus, HasTranslations, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'excerpt', 'body'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
