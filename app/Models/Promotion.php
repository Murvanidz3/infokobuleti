<?php

namespace App\Models;

use App\Enums\PromotionType;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A paid placement on a hotel, property, classified or vacancy. Saving one refreshes the
 * promotable's cached promotion columns.
 */
#[Fillable(['promotable_type', 'promotable_id', 'user_id', 'payment_id', 'order_id', 'type', 'starts_at', 'ends_at', 'cancelled_at', 'next_run_at', 'expiry_notified_at'])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory, LogsStaffActivity;

    protected static function booted(): void
    {
        $refresh = function (Promotion $promotion): void {
            $promotable = $promotion->promotable()->withoutGlobalScopes()->first();

            if ($promotable !== null && method_exists($promotable, 'refreshPromotionCache')) {
                $promotable->refreshPromotionCache();
            }
        };

        static::saved($refresh);
        static::deleted($refresh);
    }

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'next_run_at' => 'datetime',
            'expiry_notified_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('cancelled_at')
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
