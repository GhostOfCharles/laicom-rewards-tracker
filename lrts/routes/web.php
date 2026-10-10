<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PremiumProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ClaimsQueueController;
use App\Http\Controllers\Admin\NavCountController;
use App\Http\Controllers\ReceiptSlipController;
use App\Http\Controllers\TicketAttachmentController;

// Protected Customer Routes
Route::middleware(['auth', 'customer'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'index'])->name('customer.dashboard');

    // Handle order form submission
    Route::post('/dashboard/submit-order', [CustomerController::class, 'submitOrder'])->name('customer.submit_order');
    Route::post('/receipts/{receipt}/claim', [CustomerController::class, 'claimReceipt'])->name('customer.receipts.claim');
    Route::post('/receipts/{receipt}/confirm-received', [CustomerController::class, 'confirmRewardsReceived'])->name('customer.receipts.confirm-received');
    Route::post('/receipts/{receipt}/cancel', [CustomerController::class, 'cancelReceipt'])->name('customer.receipts.cancel');
    Route::get('/receipts/{receipt}/slip', [ReceiptSlipController::class, 'customer'])->name('customer.receipts.slip');
    Route::post('/notifications/read', [CustomerController::class, 'markNotificationsRead'])->name('customer.notifications.read');

    // Support tickets (customer) — rate-limited to prevent spam
    Route::post('/tickets', [TicketController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('customer.tickets.store');

    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])
        ->middleware('throttle:20,1')
        ->name('customer.tickets.reply');
    Route::get('/tickets/attachments/{reply}', [TicketAttachmentController::class, 'customer'])->name('customer.tickets.attachment');
});

// 1. The main Entry Portal (Wireframe 1)
Route::get('/', function () {
    return view('portal');
})->name('portal');

// 2. Customer Login (Wireframe 2)
Route::get('/login', function () {
    return view('auth.customer-login');
})->name('login.customer');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.customer.submit');

// 3. Customer Registration (Wireframe 3)
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register.customer');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.customer.submit');

// 4. Admin Login (Wireframe 4)
Route::get('/admin/login', function () {
    return view('auth.admin-login');
})->name('login.admin');
Route::post('/admin/login', [WebAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.admin.submit');

// 5. Logout
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/nav-counts', NavCountController::class)->name('admin.nav-counts');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Inventory — explicit routes so sidebar link (admin.inventory) keeps working
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('admin.inventory.store');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('admin.inventory.update');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('admin.inventory.destroy');

    // Reports (still static — placeholder for now)
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');

    // Receipts — reads from DB via ClaimController
    Route::get('/receipts', [ClaimController::class, 'index'])->name('admin.receipts');
    Route::get('/receipts/{receipt}/slip', [ReceiptSlipController::class, 'admin'])->name('admin.receipts.slip');

    // Approve / Reject actions
    Route::post('/receipts/{id}/approve', [ClaimController::class, 'approve'])->name('admin.receipts.approve');
    Route::post('/receipts/{id}/reject', [ClaimController::class, 'reject'])->name('admin.receipts.reject');
    Route::post('/receipts/{receipt}/release', [ClaimController::class, 'release'])->name('admin.receipts.release');
    Route::post('/rewards/{reward}/release', [ClaimController::class, 'releaseReward'])->name('admin.rewards.release');
    Route::post('/rewards/{reward}/void', [ClaimController::class, 'voidReward'])->name('admin.rewards.void');

    Route::get('/claims', [ClaimsQueueController::class, 'index'])->name('admin.claims');
    Route::post('/claims/{code}/release', [ClaimsQueueController::class, 'release'])->name('admin.claims.release');
    Route::post('/claims/receipt/{receipt}/release', [ClaimsQueueController::class, 'releaseReceipt'])->name('admin.claims.release-receipt');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('admin.activity-log');
    Route::get('/tickets/attachments/{reply}', [TicketAttachmentController::class, 'admin'])->name('admin.tickets.attachment');
    Route::get('/products/{product}/stock-history', [PremiumProductController::class, 'stockHistory'])->name('admin.products.stock-history');

    // Support tickets (admin)
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('admin.tickets');
    Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
    Route::post('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('admin.tickets.status');

    Route::post('/products', [PremiumProductController::class, 'store'])->name('products.store');
    Route::put('/products/{id}', [PremiumProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [PremiumProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
    Route::put('/promotions/{id}', [PromotionController::class, 'update'])->name('promotions.update');
    Route::delete('/promotions/{id}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
});
