<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Account;
use App\Models\Client;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function store(
        StoreTransactionRequest $request,
        Client $client,
        TransactionService $transactionService
    ): JsonResponse {
        /** @var Account $account */
        $account = $client->account;

        $transaction = $transactionService->createTransaction(
            $account,
            $request->validated()
        );

        return response()->json($transaction, 201);
    }

    public function index(Client $client): JsonResponse
    {
        /** @var Account $account */
        $account = $client->account;

        $transactions = $account
            ->transactions()
            ->latest()
            ->get();

        return response()->json($transactions);
    }

    public function balance(
        Client $client,
        TransactionService $transactionService
    ): JsonResponse {
        /** @var Account $account */
        $account = $client->account;

        $balance = $transactionService->getCashBalance($account);

        return response()->json([
            'currency' => $account->currency,
            'balance' => $balance,
        ]);
    }

    public function holdings(
        Client $client,
        TransactionService $transactionService
    ): JsonResponse {
        /** @var Account $account */
        $account = $client->account;

        $holdings = $transactionService->getHoldings($account);

        return response()->json([
            'holdings' => $holdings,
        ]);
    }
}
