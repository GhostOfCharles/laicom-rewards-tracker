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

    // Inventory UI preview route
    Route::get('/inventory', function () {
        return view('admin.inventory');
    })->name('admin.inventory');

    // Reports UI preview route
    Route::get('/reports', function () {
        return view('admin.reports');
    })->name('admin.reports');

    // Receipts UI preview route
    Route::get('/receipts', function () {
        return view('admin.receipts');
    })->name('admin.receipts');

    Route::resource('products', PremiumProductController::class);
    Route::resource('promotions', PromotionController::class);
    Route::get('/claims', [ClaimController::class, 'index'])->name('admin.claims.index');
});