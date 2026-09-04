<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Client;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $transactionService = app(TransactionService::class);

        $ana = Client::create([
            'name' => 'Ana',
        ]);

        $ana->account()->create([
            'currency' => 'EUR',
        ]);

        /** @var Account $anaAccount */
        $anaAccount = $ana->account;

        $transactionService->createTransaction($anaAccount, [
            'type' => 'deposit',
            'amount' => 1000,
        ]);

        $transactionService->createTransaction($anaAccount, [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]);

        $transactionService->createTransaction($anaAccount, [
            'type' => 'sell',
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price' => 120,
        ]);

        $marko = Client::create([
            'name' => 'Marko',
        ]);

        $marko->account()->create([
            'currency' => 'USD',
        ]);

        /** @var Account $markoAccount */
        $markoAccount = $marko->account;

        $transactionService->createTransaction($markoAccount, [
            'type' => 'deposit',
            'amount' => 2000,
        ]);

        $transactionService->createTransaction($markoAccount, [
            'type' => 'buy',
            'instrument' => 'MSFT',
            'quantity' => 10,
            'price' => 150,
        ]);
    }
}
