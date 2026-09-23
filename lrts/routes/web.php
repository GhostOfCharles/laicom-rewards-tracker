<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PremiumProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\CustomerController;

// Protected Customer Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'index'])->name('customer.dashboard');

    // Handle order form submission
    Route::post('/dashboard/submit-order', [CustomerController::class, 'submitOrder'])->name('customer.submit_order');
});

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

    // Inventory — explicit routes so sidebar link (admin.inventory) keeps working
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('admin.inventory.store');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('admin.inventory.update');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('admin.inventory.destroy');

    // Reports (still static — placeholder for now)
    Route::get('/reports', function () {
        return view('admin.reports');
    })->name('admin.reports');

    // Receipts — reads from DB via ClaimController
    Route::get('/receipts', [ClaimController::class, 'index'])->name('admin.receipts');

    // Approve / Reject actions
    Route::post('/receipts/{id}/approve', [ClaimController::class, 'approve'])->name('admin.receipts.approve');
    Route::post('/receipts/{id}/reject', [ClaimController::class, 'reject'])->name('admin.receipts.reject');

    Route::resource('products', PremiumProductController::class);
    Route::resource('promotions', PromotionController::class);
});