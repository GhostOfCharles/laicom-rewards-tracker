<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PremiumProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Auth\WebAuthController;

// 1. The main Entry Portal (Wireframe 1)
Route::get('/', function () {
    return view('portal');
})->name('portal');

// 2. Customer Login (Wireframe 2)
Route::get('/login', function () {
    return view('auth.customer-login');
})->name('login.customer');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.customer.submit');

// 3. Customer Registration (Wireframe 3)
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register.customer');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.customer.submit');

// 4. Admin Login (Wireframe 4)
Route::get('/admin/login', function () {
    return view('auth.admin-login');
})->name('login.admin');
Route::post('/admin/login', [WebAuthController::class, 'login'])->name('login.admin.submit');

// 5. Logout
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

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

    // Receipts — reads from DB via ClaimController
    Route::get('/receipts', [ClaimController::class, 'index'])->name('admin.receipts');

    Route::resource('products', PremiumProductController::class);
    Route::resource('promotions', PromotionController::class);
});