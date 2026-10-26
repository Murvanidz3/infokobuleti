<?php

namespace App\Models;

use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\BusinessSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paid monthly business package: more active listings, the verified badge, the logo on cards
 * and listing analytics, from starts_at until ends_at.
 */
#[Fillable(['user_id', 'order_id', 'price_plan_id', 'listings_limit', 'starts_at', 'ends_at', 'cancelled_at', 'expiry_notified_at'])]
class BusinessSubscription extends Model
{
    /** @use HasFactory<BusinessSubscriptionFactory> */
    use HasFactory, LogsStaffActivity;

    protected function casts(): array
    {
        return [
            'listings_limit' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expiry_notified_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('cancelled_at')->where('starts_at', '<=', now())->where('ends_at', '>', now());
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
     * @return BelongsTo<PricePlan, $this>
     */
    public function pricePlan(): BelongsTo
    {
        return $this->belongsTo(PricePlan::class);
    }
}
