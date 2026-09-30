<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:200',
            'category' => 'required|in:orders,rewards,promos,account,other',
            'related_receipt_id' => 'nullable|exists:receipts,id',
            'body' => 'required|string|max:5000',
        ]);

        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'category' => $request->category,
            'status' => 'open',
            'priority' => 'normal',
            'related_receipt_id' => $request->related_receipt_id,
        ]);

        $ticket->replies()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
            'is_internal_note' => false,
        ]);

        return redirect()
            ->route('customer.dashboard', ['tab' => 'help', 'open_drawer' => 1])
            ->with('success', 'Inquiry submitted. We will reply within 24 hours.');
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);
        abort_if($ticket->status === 'closed', 403, 'This ticket is closed.');

        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $ticket->replies()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
            'is_internal_note' => false,
        ]);

        // Reopen if it was resolved
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open', 'resolved_at' => null]);
        }

        return redirect()
            ->route('customer.dashboard', ['tab' => 'help', 'open_drawer' => 1, 'ticket' => $ticket->id])
            ->with('success', 'Reply sent.');
    }
}