<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\ClaimsQueueController;
use App\Http\Controllers\Admin\CustomerAccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NavCountController;
use App\Http\Controllers\Admin\PremiumProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\PulseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReceiptSlipController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketRepliesController;
use Illuminate\Support\Facades\Route;

// Protected Customer Routes
Route::middleware(['auth', 'customer', 'password.current'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'index'])->name('customer.dashboard');

    // Handle order form submission
    Route::post('/dashboard/submit-order', [CustomerController::class, 'submitOrder'])->middleware('throttle:submit-order')->name('customer.submit_order');
    Route::post('/receipts/{receipt}/claim', [CustomerController::class, 'claimReceipt'])->name('customer.receipts.claim');
    Route::post('/receipts/{receipt}/confirm-received', [CustomerController::class, 'confirmRewardsReceived'])->name('customer.receipts.confirm-received');
    Route::post('/receipts/{receipt}/cancel', [CustomerController::class, 'cancelReceipt'])->name('customer.receipts.cancel');
    Route::get('/receipts/{receipt}/slip', [ReceiptSlipController::class, 'customer'])->name('customer.receipts.slip');
    Route::post('/notifications/read', [CustomerController::class, 'markNotificationsRead'])->name('customer.notifications.read');

    // Support tickets (customer) — rate-limited to prevent spam
    Route::post('/tickets', [TicketController::class, 'store'])
        ->middleware('throttle:login')
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
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login')->name('login.customer.submit');

// 3. Customer Registration (Wireframe 3)
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register.customer');
Route::post('/register', [WebAuthController::class, 'register'])->middleware('throttle:register')->name('register.customer.submit');

// 4. Admin Login (Wireframe 4)
Route::get('/admin/login', function () {
    return view('auth.admin-login');
})->name('login.admin');
Route::post('/admin/login', [WebAuthController::class, 'login'])->middleware('throttle:login')->name('login.admin.submit');

// 5. Logout
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/nav-counts', NavCountController::class)->middleware('throttle:pulse')->name('admin.nav-counts');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Inventory — explicit routes so sidebar link (admin.inventory) keeps working
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('admin.inventory.store');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('admin.inventory.update');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('admin.inventory.destroy');

    // Reports and exports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');

    // Receipts — reads from DB via ClaimController
    Route::get('/receipts', [ClaimController::class, 'index'])->name('admin.receipts');
    Route::get('/receipts/{receipt}/slip', [ReceiptSlipController::class, 'admin'])->name('admin.receipts.slip');

    Route::get('/receipts/{receipt}/panel', [ClaimController::class, 'panel'])->name('admin.receipts.panel');

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

Route::middleware(['auth', 'customer', 'password.current'])->group(function () {
    Route::get('/account', [AccountController::class, 'profile'])->name('customer.profile');
    Route::patch('/account', [AccountController::class, 'update'])->middleware('throttle:10,1')->name('customer.profile.update');
    Route::put('/account/password', [AccountController::class, 'password'])->middleware('throttle:5,1')->name('customer.password');
    Route::get('/notifications', [AccountController::class, 'notifications'])->name('customer.notifications');
    Route::post('/notifications/{notification}/read', [AccountController::class, 'read'])->name('customer.notifications.item.read');
});

Route::get('/forgot-password', [PasswordResetController::class, 'form'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,60')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,60')->name('password.update');
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/customers', [CustomerAccountController::class, 'index'])->name('admin.customers');
    Route::put('/customers/{customer}/temporary-password', [CustomerAccountController::class, 'temporaryPassword'])->middleware('throttle:5,60')->name('admin.customers.password');
});

Route::middleware(['auth', 'customer', 'password.current', 'throttle:pulse'])->group(function () {
    Route::get('/dashboard/pulse', PulseController::class)->name('customer.pulse');
    Route::get('/dashboard/tickets/{ticket}/replies', [TicketRepliesController::class, 'index'])->name('customer.tickets.replies');
    Route::post('/dashboard/tickets/{ticket}/read', [TicketRepliesController::class, 'read'])->name('customer.tickets.read');
});
Route::get('/admin/tickets/{ticket}/replies', [TicketRepliesController::class, 'index'])->middleware(['auth', 'admin', 'throttle:pulse'])->name('admin.tickets.replies');

Route::get('/notifications/preview', [AccountController::class, 'preview'])->middleware(['auth', 'customer', 'password.current', 'throttle:pulse'])->name('customer.notifications.preview');
