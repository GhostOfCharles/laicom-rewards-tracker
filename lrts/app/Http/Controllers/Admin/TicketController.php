<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\CustomerNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

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

    public function reply(Request $request, Ticket $ticket, ActivityLogger $activityLogger)
    {
        $request->validate([
            'body' => 'required|string|min:10|max:5000',
            'is_internal_note' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:5120',
        ]);

        $isInternal = $request->boolean('is_internal_note');

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket-attachments', 'private');
        }

        try {
            DB::transaction(function () use ($ticket, $isInternal, $request, $attachmentPath, $activityLogger) {
                $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
                $locked->replies()->create([
                    'user_id' => Auth::id(),
                    'body' => $request->body,
                    'is_internal_note' => $isInternal,
                    'attachment_path' => $attachmentPath,
                ]);
                if ($isInternal) {
                    $activityLogger->record('ticket.internal_note', 'Internal note added to ticket #' . $locked->id . '.', $locked);
                } else {
                    $activityLogger->record('ticket.replied', 'Admin replied to ticket #' . $locked->id . '.', $locked);
                    CustomerNotification::create([
                        'user_id' => $locked->user_id,
                        'type' => 'ticket.replied',
                        'title' => 'Support replied',
                        'body' => 'Support replied to your ticket: ' . $locked->subject,
                        'receipt_id' => $locked->related_receipt_id,
                        'created_at' => now(),
                    ]);
                    if (in_array($locked->status, ['open', 'in_progress'], true)) {
                        $oldStatus = $locked->status;
                        $locked->update(['status' => 'pending', 'resolved_at' => null]);
                        $activityLogger->record('ticket.status_changed', 'Ticket #' . $locked->id . ' moved to pending after admin reply.', $locked, ['old' => $oldStatus, 'new' => 'pending']);
                    }
                }
            });
        } catch (Throwable $exception) {
            if ($attachmentPath) Storage::disk('private')->delete($attachmentPath);
            throw $exception;
        }

        return redirect()
            ->route('admin.tickets', ['ticket' => $ticket->id, 'status' => $request->query('status', 'all')])
            ->with('success', $isInternal ? 'Internal note added.' : 'Reply sent.');
    }

    public function updateStatus(Request $request, Ticket $ticket, ActivityLogger $activityLogger)
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

        DB::transaction(function () use ($ticket, $updates, $newStatus, $activityLogger) {
            $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            $oldStatus = $locked->status;
            $locked->update($updates);
            if ($oldStatus !== $newStatus) {
                $activityLogger->record('ticket.status_changed', 'Ticket #' . $locked->id . ' marked as ' . strtoupper(str_replace('_', ' ', $newStatus)) . '.', $locked, ['old' => $oldStatus, 'new' => $newStatus]);
            }
        });

        return redirect()
            ->route('admin.tickets', ['ticket' => $ticket->id, 'status' => $request->query('status', 'all')])
            ->with('success', 'Ticket marked as ' . strtoupper(str_replace('_', ' ', $newStatus)) . '.');
    }
}
