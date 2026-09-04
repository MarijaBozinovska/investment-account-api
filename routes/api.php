<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/clients', [ClientController::class, 'store']);

Route::post('/clients/{client}/transactions', [TransactionController::class, 'store']);
Route::get('/clients/{client}/transactions', [TransactionController::class, 'index']);
Route::get('/clients/{client}/balance', [TransactionController::class, 'balance']);
Route::get('/clients/{client}/holdings', [TransactionController::class, 'holdings']);
