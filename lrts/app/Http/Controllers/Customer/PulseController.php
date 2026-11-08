<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\PulseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PulseController extends Controller
{
    public function __invoke(Request $r)
    {
        $id = $r->user()->id;
        $data = Cache::remember('rts-pulse:customer:'.$id, 2, function () use ($r, $id) {
            $receipts = Receipt::where('user_id', $id)->latest('id')->limit(10)->get(['id', 'status', 'updated_at']);
            $rewards = EarnedReward::where('user_id', $id)->latest('id')->limit(100)->get(['id', 'receipt_id', 'claim_status', 'updated_at']);
            $publicReplies = TicketReply::whereHas('ticket', fn ($q) => $q->where('user_id', $id))->where('is_internal_note', false);
            $unseen = Ticket::where('user_id', $id)->whereHas('replies', fn ($q) => $q->where('is_internal_note', false)->whereColumn('ticket_replies.id', '>', 'tickets.customer_seen_reply_id')->whereHas('user', fn ($u) => $u->where('role', 'admin')))->count();
            $version = hash('sha256', json_encode([
                Receipt::where('user_id', $id)->orderBy('id')->get(['id', 'status', 'updated_at']),
                EarnedReward::where('user_id', $id)->orderBy('id')->get(['id', 'receipt_id', 'claim_status', 'updated_at']),
                Ticket::where('user_id', $id)->orderBy('id')->get(['id', 'status', 'updated_at']),
                $r->user()->customerNotifications()->orderBy('id')->get(['id', 'read_at']),
                (clone $publicReplies)->max('id'),
                Promotion::with('premiumProduct')->orderBy('id')->get(),
                \App\Services\BusinessDate::today()->toDateString(),
            ]));

            return ['unread_notifications' => $r->user()->customerNotifications()->whereNull('read_at')->count(), 'pending_receipts' => $r->user()->receipts()->where('status', 'pending')->count(), 'unseen_support_tickets' => $unseen, 'receipts' => $receipts->map->only(['id', 'status'])->all(), 'rewards' => $rewards->map->only(['id', 'claim_status'])->all(), 'version' => $version];
        });

        return PulseResponse::make($r,$data);
    }
}
