<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::post('/clients', [ClientController::class, 'store']);