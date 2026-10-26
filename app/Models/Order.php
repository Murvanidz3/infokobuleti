<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricePlanProduct;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;

/**
 * One purchase: a promotion for a listing, a business package, a wallet top-up or a staff-issued
 * invoice. Paid by card (payments) or from the wallet. The invoice number is given once it is paid.
 *
 * @property array<string, mixed>|null $options
 */
#[Fillable(['user_id', 'price_plan_id', 'product', 'purchasable_type', 'purchasable_id', 'description', 'amount', 'currency', 'method', 'options', 'issued_by'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasTranslations, LogsStaffActivity;

    /** @var list<string> */
    public array $translatable = ['description'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending', 'currency' => 'GEL'];

    protected function casts(): array
    {
        return [
            'product' => PricePlanProduct::class,
            'status' => OrderStatus::class,
            'method' => PaymentMethod::class,
            'currency' => Currency::class,
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'options' => 'array',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Gives the order its invoice number (IK-2026-000042) if it has none yet.
     */
    public function assignNumber(): void
    {
        if ($this->number !== null) {
            return;
        }

        $next = (int) DB::selectOne("select nextval('invoice_number_seq') as n")->n;

        $this->forceFill([
            'number' => sprintf('%s-%s-%06d', config('payments.invoice.prefix'), now()->format('Y'), $next),
        ])->save();
    }

    public function refundableAmount(): float
    {
        return round((float) $this->amount - (float) $this->refunded_amount, 2);
    }

    /**
     * A name for receipts and lists: the plan name, or the staff-written description.
     */
    public function title(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $description = $this->getTranslation('description', $locale, true);

        if (is_string($description) && $description !== '') {
            return $description;
        }

        if ($this->relationLoaded('pricePlan') && $this->pricePlan !== null) {
            return (string) $this->pricePlan->getTranslation('name', $locale, true);
        }

        return __('enums.price_plan_product.'.$this->product->value, locale: $locale);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PricePlan, $this>
     */
    public function pricePlan(): BelongsTo
    {
        return $this->belongsTo(PricePlan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * The listing being promoted, or the banner being bought.
     *
     * @return MorphTo<Model, $this>
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * @return HasOne<BusinessSubscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(BusinessSubscription::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
