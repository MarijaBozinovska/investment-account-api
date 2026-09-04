<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = DB::transaction(function () use ($request) {
            $client = Client::create([
                'name' => $request->validated('name'),
            ]);

            $client->account()->create([
                'currency' => strtoupper($request->validated('currency')),
            ]);

            return $client->load('account');
        });

        return response()->json($client, 201);
    }
}
