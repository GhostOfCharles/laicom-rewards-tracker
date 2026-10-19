<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public route for Flutter mobile login
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Protected routes (requires Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        abort_unless($request->user()->tokenCan('user:read'), 403);

        return $request->user();
    });

    // The endpoint to submit the physical order number and items
    Route::post('/receipts', [ReceiptController::class, 'store'])->middleware('throttle:submit-order');
});
