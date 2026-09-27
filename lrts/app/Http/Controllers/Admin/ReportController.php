<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->query('range', 30);
        $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
        $from = Carbon::now()->subDays($days - 1)->startOfDay();

        $summary = [
            'submitted' => Receipt::where('submitted_at', '>=', $from)->count(),
            'approved' => Receipt::where('submitted_at', '>=', $from)->where('status', 'approved')->count(),
            'rejected' => Receipt::where('submitted_at', '>=', $from)->where('status', 'rejected')->count(),
            'rewards_issued' => EarnedReward::where('created_at', '>=', $from)->sum('reward_quantity'),
            'rewards_claimed' => EarnedReward::where('claimed_at', '>=', $from)->where('claim_status', 'claimed')->sum('reward_quantity'),
        ];

        return view('admin.reports', compact('days', 'from', 'summary'));
    }
}
