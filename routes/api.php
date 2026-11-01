<?php

use App\Http\Controllers\Api\V1\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/wallets/transfer', TransferController::class);
});

// Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
//     Route::post('/wallets/transfer', TransferController::class);
// });