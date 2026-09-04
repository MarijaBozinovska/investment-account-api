<?php

namespace Database\Seeders;

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

        $transactionService->createTransaction($ana->account, [
            'type' => 'deposit',
            'amount' => 1000,
        ]);

        $transactionService->createTransaction($ana->account, [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]);

        $transactionService->createTransaction($ana->account, [
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

        $transactionService->createTransaction($marko->account, [
            'type' => 'deposit',
            'amount' => 2000,
        ]);

        $transactionService->createTransaction($marko->account, [
            'type' => 'buy',
            'instrument' => 'MSFT',
            'quantity' => 10,
            'price' => 150,
        ]);
    }
}