<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReceiptController;

// Public route for Flutter mobile login
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (requires Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // The endpoint to submit the physical order number and items
    Route::post('/receipts', [ReceiptController::class, 'store']);
});