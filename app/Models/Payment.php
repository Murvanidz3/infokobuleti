<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Guarded(['id', 'status', 'paid_at', 'refunded_amount', 'refunded_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsStaffActivity;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending'];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->idempotency_key ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'status' => PaymentStatus::class,
            'currency' => Currency::class,
            'amount' => 'float',
            'refunded_amount' => 'float',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
