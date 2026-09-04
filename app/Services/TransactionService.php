<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function getCashBalance(Account $account): float
    {
        return (float) $account->transactions()
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN type = 'deposit' THEN amount
                        WHEN type = 'sell' THEN amount
                        WHEN type = 'withdrawal' THEN -amount
                        WHEN type = 'buy' THEN -amount
                        ELSE 0
                    END
                ), 0) as balance
            ")
            ->value('balance');
    }

    /**
     * @return array<string, int>
     */
    public function getHoldings(Account $account): array
    {
        return $account->transactions()
            ->selectRaw("
            instrument,
            SUM(
                CASE
                    WHEN type = 'buy' THEN quantity
                    WHEN type = 'sell' THEN -quantity
                    ELSE 0
                END
            ) as quantity
        ")
            ->whereIn('type', ['buy', 'sell'])
            ->groupBy('instrument')
            ->havingRaw('SUM(
            CASE
                WHEN type = \'buy\' THEN quantity
                WHEN type = \'sell\' THEN -quantity
                ELSE 0
            END
        ) > 0')
            ->pluck('quantity', 'instrument')
            ->map(fn ($quantity) => (int) $quantity)
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTransaction(Account $account, array $data): Transaction
    {
        return DB::transaction(function () use ($account, $data) {
            $type = $data['type'];

            return match ($type) {
                'deposit' => $this->createDeposit($account, $data),
                'withdrawal' => $this->createWithdrawal($account, $data),
                'buy' => $this->createBuy($account, $data),
                'sell' => $this->createSell($account, $data),
                default => throw new InvalidArgumentException('Invalid transaction type.'),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDeposit(Account $account, array $data): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = $account->transactions()->create([
            'type' => 'deposit',
            'amount' => $data['amount'],
            'instrument' => null,
            'quantity' => null,
            'price' => null,
        ]);

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createWithdrawal(Account $account, array $data): Transaction
    {
        $amount = (float) $data['amount'];

        if ($amount > $this->getCashBalance($account)) {
            throw new InvalidArgumentException(
                'Insufficient cash balance for this withdrawal.'
            );
        }

        /** @var Transaction $transaction */
        $transaction = $account->transactions()->create([
            'type' => 'withdrawal',
            'amount' => $amount,
            'instrument' => null,
            'quantity' => null,
            'price' => null,
        ]);

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createBuy(Account $account, array $data): Transaction
    {
        $quantity = (int) $data['quantity'];
        $price = (float) $data['price'];
        $amount = round($quantity * $price, 2);

        if ($amount > $this->getCashBalance($account)) {
            throw new InvalidArgumentException(
                'Insufficient cash balance for this purchase.'
            );
        }

        /** @var Transaction $transaction */
        $transaction = $account->transactions()->create([
            'type' => 'buy',
            'amount' => $amount,
            'instrument' => $data['instrument'],
            'quantity' => $quantity,
            'price' => $price,
        ]);

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createSell(Account $account, array $data): Transaction
    {
        $instrument = $data['instrument'];
        $quantity = (int) $data['quantity'];
        $price = (float) $data['price'];

        $holdings = $this->getHoldings($account);
        $ownedQuantity = $holdings[$instrument] ?? 0;

        if ($quantity > $ownedQuantity) {
            throw new InvalidArgumentException(
                'Insufficient holdings for this sale.'
            );
        }

        $amount = round($quantity * $price, 2);

        /** @var Transaction $transaction */
        $transaction = $account->transactions()->create([
            'type' => 'sell',
            'amount' => $amount,
            'instrument' => $instrument,
            'quantity' => $quantity,
            'price' => $price,
        ]);

        return $transaction;
    }
}
