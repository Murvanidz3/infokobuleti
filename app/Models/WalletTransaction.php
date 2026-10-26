<?php

namespace App\Models;

use App\Enums\WalletEntryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the wallet ledger. Rows are only ever added (see App\Services\Billing\Wallet).
 */
#[Fillable(['user_id', 'order_id', 'type', 'amount', 'balance_after', 'note', 'created_by'])]
class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => WalletEntryType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
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
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
