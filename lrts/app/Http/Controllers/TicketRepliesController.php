<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PulseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class TicketRepliesController extends Controller
{
    public function index(Request $r, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);
        $after = (int) ($r->validate(['after' => 'nullable|integer|min:0'])['after'] ?? 0);
        $admin = $r->user()->role === 'admin';
        $data = Cache::remember('rts-replies:'.$ticket->id.':'.($admin ? 'admin' : 'customer').':'.$after, 5, function () use ($ticket, $admin, $after) {
            $messages = $ticket->replies()->where('id', '>', $after)->when(! $admin, fn ($q) => $q->where('is_internal_note', false))->with('user:id,name,role')->orderBy('id')->limit(100)->get();

            return ['status' => $ticket->fresh()->status, 'messages' => $messages->map(fn ($reply) => ['id' => $reply->id, 'body' => $reply->body, 'author' => $reply->user?->role === 'admin' ? 'Support' : 'Customer', 'created_at' => $reply->created_at->toIso8601String(), 'is_internal_note' => $admin && $reply->is_internal_note, 'attachment_url' => $reply->attachment_path ? route($admin ? 'admin.tickets.attachment' : 'customer.tickets.attachment', $reply) : null])->all()];
        });

        return PulseResponse::make($r, $data);
    }

    public function read(Request $r, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);
        $id = (int) ($r->validate(['after' => 'required|integer|min:0'])['after']);
        $max = (int) $ticket->replies()->where('is_internal_note', false)->where('id', '<=', $id)->max('id');
        Ticket::whereKey($ticket->id)->where('customer_seen_reply_id', '<', $max)->update(['customer_seen_reply_id' => $max]);
        Cache::forget('rts-pulse:customer:'.$r->user()->id);

        return response()->json(['ok' => true]);
    }
}
