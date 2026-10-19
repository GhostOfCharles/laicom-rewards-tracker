<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\PulseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NavCountController extends Controller
{
    public function __invoke(Request $r)
    {
        $data = Cache::remember('rts-pulse:admin', 5, fn () => [
            'receipts' => Receipt::where('status', 'pending')->count(),
            'claims' => EarnedReward::where('claim_status', 'claim_requested')->distinct('receipt_id')->count('receipt_id'),
            'tickets' => Ticket::whereIn('status', ['open', 'pending', 'in_progress'])->count(),
            'premium-expiry' => PremiumProduct::where('is_perishable', true)->whereDate('expiry_date', '<=', today()->addDays(30))->count(),
            'version' => hash('sha256', json_encode([Receipt::count(), Receipt::max('updated_at'), EarnedReward::count(), EarnedReward::max('updated_at'), Ticket::count(), Ticket::max('updated_at'), TicketReply::max('id')])),
        ]);

        return PulseResponse::make($r, $data);
    }
}
