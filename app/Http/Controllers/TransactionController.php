<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
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
        $transaction = $transactionService->createTransaction(
            $client->account,
            $request->validated()
        );

        return response()->json($transaction, 201);
    }

    public function index(Client $client): JsonResponse
    {
        $transactions = $client->account
            ->transactions()
            ->latest()
            ->get();

        return response()->json($transactions);
    }

    public function balance(
        Client $client,
        TransactionService $transactionService
    ): JsonResponse {
        $balance = $transactionService->getCashBalance($client->account);

        return response()->json([
            'currency' => $client->account->currency,
            'balance' => $balance,
        ]);
    }

    public function holdings(
        Client $client,
        TransactionService $transactionService
    ): JsonResponse {
        $holdings = $transactionService->getHoldings($client->account);

        return response()->json([
            'holdings' => $holdings,
        ]);
    }
}
