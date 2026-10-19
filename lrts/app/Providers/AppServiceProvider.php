<?php

namespace App\Providers;

use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\User;
use App\Observers\ProductImageObserver;
use App\Services\LoginProtection;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn ($r) => Limit::perMinute(5)->by(LoginProtection::key($r)));
        RateLimiter::for('register', fn ($r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('submit-order', fn ($r) => [Limit::perMinute(10)->by('minute:'.$r->user()->id), Limit::perDay(40)->by('day:'.$r->user()->id)]);
        RateLimiter::for('pulse', fn ($r) => Limit::perMinute(60)->by($r->user()->id));
        Inventory::observe(ProductImageObserver::class);
        PremiumProduct::observe(ProductImageObserver::class);
        Gate::define('release-rewards', fn (User $user) => $user->role === 'admin');

        View::composer('layouts.admin', function ($view) {
            if (Auth::user()?->role !== 'admin') {
                $view->with('adminNavCounts', ['receipts' => 0, 'claims' => 0, 'tickets' => 0]);

                return;
            }

            $view->with('adminNavCounts', [
                'receipts' => Receipt::where('status', 'pending')->count(),
                'claims' => EarnedReward::where('claim_status', 'claim_requested')->distinct('receipt_id')->count('receipt_id'),
                'tickets' => Ticket::whereIn('status', ['open', 'pending', 'in_progress'])->count(),
            ]);
        });
    }
}
