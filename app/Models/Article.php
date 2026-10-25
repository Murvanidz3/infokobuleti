<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Search\SearchableListing;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\IndexedForSearch;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A news article. user_id is the author.
 *
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $body
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Article extends Model implements Listing, SearchableListing
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasMediaGallery, HasSlug, HasStatus, IndexedForSearch, HasTranslations, LogsStaffActivity, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'excerpt', 'body'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Same as author(); every listing module exposes its owner as user().
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    /**
     * @return list<string>
     */
    public static function searchColumns(): array
    {
        return ['title', 'excerpt'];
    }
}
