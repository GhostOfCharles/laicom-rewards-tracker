<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PremiumProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ClaimController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('products', PremiumProductController::class);
    Route::resource('promotions', PromotionController::class);
    Route::get('/claims', [ClaimController::class, 'index'])->name('admin.claims.index');
});