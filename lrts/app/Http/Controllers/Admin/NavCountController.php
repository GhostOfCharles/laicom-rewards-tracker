<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Models\Receipt;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class NavCountController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'receipts' => Receipt::where('status', 'pending')->count(),
            'claims' => EarnedReward::where('claim_status', 'claim_requested')->distinct('receipt_id')->count('receipt_id'),
            'tickets' => Ticket::whereIn('status', ['open', 'pending', 'in_progress'])->count(),
            'premium-expiry' => PremiumProduct::where('is_perishable', true)
                ->whereDate('expiry_date', '<=', today()->addDays(30)->toDateString())
                ->count(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
