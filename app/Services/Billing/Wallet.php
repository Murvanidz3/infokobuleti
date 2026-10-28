<?php

namespace App\Services\Billing;

use App\Enums\WalletEntryType;
use App\Exceptions\InsufficientBalance;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The prepaid balance. Every change locks the user row, writes one ledger line and the new
 * balance together, so concurrent purchases can never spend the same money twice.
 */
class Wallet
{
    public function credit(User $user, float $amount, WalletEntryType $type, ?Order $order = null, ?string $note = null, ?User $author = null): WalletTransaction
    {
        return $this->change($user, abs($amount), $type, $order, $note, $author);
    }

    /**
     * @throws InsufficientBalance
     */
    public function debit(User $user, float $amount, WalletEntryType $type, ?Order $order = null, ?string $note = null, ?User $author = null): WalletTransaction
    {
        return $this->change($user, -abs($amount), $type, $order, $note, $author);
    }

    public function balance(User $user): float
    {
        return (float) User::query()->whereKey($user->getKey())->value('balance');
    }

    private function change(User $user, float $amount, WalletEntryType $type, ?Order $order, ?string $note, ?User $author): WalletTransaction
    {
        if (round($amount, 2) === 0.0) {
            throw new InvalidArgumentException('A wallet change needs a non-zero amount.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $order, $note, $author): WalletTransaction {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $balance = round((float) $locked->balance + $amount, 2);

            if ($balance < 0) {
                throw new InsufficientBalance;
            }

            $locked->forceFill(['balance' => $balance])->saveQuietly();
            $user->forceFill(['balance' => $balance])->syncOriginalAttribute('balance');

            return WalletTransaction::query()->create([
                'user_id' => $locked->getKey(),
                'order_id' => $order?->getKey(),
                'type' => $type,
                'amount' => round($amount, 2),
                'balance_after' => $balance,
                'note' => $note,
                'created_by' => $author?->getKey(),
            ]);
        });
    }
}
