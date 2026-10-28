<?php

namespace App\Models;

use App\Enums\BannerSlot;
use App\Models\Concerns\LogsStaffActivity;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Guarded(['id', 'clicks'])]
class Banner extends Model
{
    use HasTranslations, LogsStaffActivity;

    /** @var list<string> */
    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'slot' => BannerSlot::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Banners that should show right now.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
