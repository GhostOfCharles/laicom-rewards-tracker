<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('status', 'all');
        $selectedId = $request->query('ticket');

        $query = Ticket::with(['user', 'replies.user'])
            ->withCount('replies')
            ->latest();

        if (in_array($filter, ['open', 'pending', 'in_progress', 'resolved', 'closed'], true)) {
            $query->where('status', $filter);
        }

        $tickets = $query->get();

        $selectedTicket = $selectedId
            ? Ticket::with(['user', 'replies.user', 'relatedReceipt'])->find($selectedId)
            : $tickets->first();

        return view('admin.tickets', compact('tickets', 'selectedTicket', 'filter'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'body' => 'required|string|min:10|max:5000',
            'is_internal_note' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:5120',
        ]);

        $isInternal = $request->boolean('is_internal_note');

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket-attachments', 'public');
        }

        $ticket->replies()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
            'is_internal_note' => $isInternal,
            'attachment_path' => $attachmentPath,
        ]);

        // Only public replies bump the status
        if (!$isInternal) {
            if (in_array($ticket->status, ['open', 'in_progress'], true)) {
                $ticket->update(['status' => 'pending', 'resolved_at' => null]);
            }
        }

        return redirect()
            ->route('admin.tickets', ['ticket' => $ticket->id, 'status' => $request->query('status', 'all')])
            ->with('success', $isInternal ? 'Internal note added.' : 'Reply sent.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,pending,in_progress,resolved,closed',
        ]);

        $newStatus = $request->status;
        $updates = ['status' => $newStatus];

        if ($newStatus === 'resolved') {
            $updates['resolved_at'] = now();
        } elseif (in_array($newStatus, ['open', 'pending', 'in_progress'], true)) {
            $updates['resolved_at'] = null;
        }

        $ticket->update($updates);

        return redirect()
            ->route('admin.tickets', ['ticket' => $ticket->id, 'status' => $request->query('status', 'all')])
            ->with('success', 'Ticket marked as ' . strtoupper(str_replace('_', ' ', $newStatus)) . '.');
    }
}