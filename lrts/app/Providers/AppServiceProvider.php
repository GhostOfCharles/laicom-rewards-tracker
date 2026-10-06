<?php

namespace App\Providers;

use App\Models\EarnedReward;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
