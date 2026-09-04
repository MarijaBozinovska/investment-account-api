<?php

use App\Models\Client;

function createClient(string $name = 'Ana', string $currency = 'EUR'): Client
{
    $client = Client::create([
        'name' => $name,
    ]);

    $client->account()->create([
        'currency' => $currency,
    ]);

    return $client->load('account');
}

test('client can deposit money', function () {
    $client = createClient();

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    );

    $response
        ->assertStatus(201)
        ->assertJsonPath('type', 'deposit')
        ->assertJsonPath('amount', 1000);

    $this->assertDatabaseHas('transactions', [
        'account_id' => $client->account->id,
        'type' => 'deposit',
        'amount' => 1000,
    ]);
});

test('client cannot withdraw more cash than available', function () {
    $client = createClient();

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'withdrawal',
            'amount' => 1001,
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Insufficient cash balance for this withdrawal.',
        ]);

    $this->assertDatabaseMissing('transactions', [
        'account_id' => $client->account->id,
        'type' => 'withdrawal',
        'amount' => 1001,
    ]);
});

test('client can buy an instrument', function () {
    $client = createClient();

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]
    );

    $response
        ->assertStatus(201)
        ->assertJsonPath('type', 'buy')
        ->assertJsonPath('amount', 500)
        ->assertJsonPath('instrument', 'AAPL')
        ->assertJsonPath('quantity', 5)
        ->assertJsonPath('price', 100);

    $this->assertDatabaseHas('transactions', [
        'account_id' => $client->account->id,
        'type' => 'buy',
        'amount' => 500,
        'instrument' => 'AAPL',
        'quantity' => 5,
        'price' => 100,
    ]);
});

test('client cannot buy an instrument without enough cash', function () {
    $client = createClient();

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 11,
            'price' => 100,
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Insufficient cash balance for this purchase.',
        ]);

    $this->assertDatabaseMissing('transactions', [
        'account_id' => $client->account->id,
        'type' => 'buy',
        'instrument' => 'AAPL',
        'quantity' => 11,
        'price' => 100,
    ]);
});

test('client can sell an instrument at a different price', function () {
    $client = createClient();

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]
    )->assertStatus(201);

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'sell',
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price' => 120,
        ]
    );

    $response
        ->assertStatus(201)
        ->assertJsonPath('type', 'sell')
        ->assertJsonPath('amount', 360)
        ->assertJsonPath('instrument', 'AAPL')
        ->assertJsonPath('quantity', 3)
        ->assertJsonPath('price', 120);

    $this->assertDatabaseHas('transactions', [
        'account_id' => $client->account->id,
        'type' => 'sell',
        'amount' => 360,
        'instrument' => 'AAPL',
        'quantity' => 3,
        'price' => 120,
    ]);
});

test('client cannot sell more units than owned', function () {
    $client = createClient();

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]
    )->assertStatus(201);

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'sell',
            'instrument' => 'AAPL',
            'quantity' => 6,
            'price' => 120,
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Insufficient holdings for this sale.',
        ]);

    $this->assertDatabaseMissing('transactions', [
        'account_id' => $client->account->id,
        'type' => 'sell',
        'instrument' => 'AAPL',
        'quantity' => 6,
        'price' => 120,
    ]);
});

test('client balance and holdings are calculated correctly', function () {
    $client = createClient();

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]
    )->assertStatus(201);

    $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'sell',
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price' => 120,
        ]
    )->assertStatus(201);

    $this->getJson("/api/clients/{$client->id}/balance")
        ->assertStatus(200)
        ->assertJson([
            'currency' => 'EUR',
            'balance' => 860,
        ]);

    $this->getJson("/api/clients/{$client->id}/holdings")
        ->assertStatus(200)
        ->assertJson([
            'holdings' => [
                'AAPL' => 2,
            ],
        ]);
});

test('clients cannot access each others transactions', function () {
    $ana = createClient('Ana', 'EUR');
    $marko = createClient('Marko', 'USD');

    $this->postJson(
        "/api/clients/{$ana->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 1000,
        ]
    )->assertStatus(201);

    $this->postJson(
        "/api/clients/{$marko->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 2000,
        ]
    )->assertStatus(201);

    $this->getJson("/api/clients/{$ana->id}/balance")
        ->assertStatus(200)
        ->assertJson([
            'currency' => 'EUR',
            'balance' => 1000,
        ]);

    $this->getJson("/api/clients/{$marko->id}/balance")
        ->assertStatus(200)
        ->assertJson([
            'currency' => 'USD',
            'balance' => 2000,
        ]);

    $this->getJson("/api/clients/{$ana->id}/transactions")
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.amount', 1000);

    $this->getJson("/api/clients/{$marko->id}/transactions")
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.amount', 2000);
});

test('client cannot create a transaction with invalid data', function () {
    $client = createClient();

    $response = $this->postJson(
        "/api/clients/{$client->id}/transactions",
        [
            'type' => 'deposit',
            'amount' => 0,
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);

    $this->assertDatabaseCount('transactions', 0);
});
