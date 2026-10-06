<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TicketController extends Controller
{
    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $request->validate([
            'subject' => 'required|string|min:3|max:200',
            'category' => 'required|in:orders,rewards,promos,account,other',
            'related_receipt_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('receipts', 'id')->where('user_id', Auth::id()),
            ],
            'body' => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket-attachments', 'private');
        }

        try {
            $ticket = DB::transaction(function () use ($request, $attachmentPath, $activityLogger) {
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
                    'attachment_path' => $attachmentPath,
                ]);
                $activityLogger->record('ticket.created', 'Support ticket #' . $ticket->id . ' created.', $ticket, ['category' => $ticket->category, 'subject' => $ticket->subject]);
                return $ticket;
            });
        } catch (Throwable $exception) {
            if ($attachmentPath) Storage::disk('private')->delete($attachmentPath);
            throw $exception;
        }

        return redirect()
            ->route('customer.dashboard', ['tab' => 'help', 'open_drawer' => 1])
            ->with('success', 'Inquiry submitted. We will reply within 24 hours.');
    }

    public function reply(Request $request, Ticket $ticket, ActivityLogger $activityLogger)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);

        if (in_array($ticket->status, ['resolved', 'closed'], true)) {
            return back()->withErrors([
                'error' => 'This ticket is no longer accepting replies. Please open a new ticket if you need further help.',
            ]);
        }

        $request->validate([
            'body' => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket-attachments', 'private');
        }

        try {
            DB::transaction(function () use ($request, $ticket, $attachmentPath, $activityLogger) {
                $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
                $locked->replies()->create([
                    'user_id' => Auth::id(),
                    'body' => $request->body,
                    'is_internal_note' => false,
                    'attachment_path' => $attachmentPath,
                ]);
                $activityLogger->record('ticket.replied', 'Customer replied to ticket #' . $locked->id . '.', $locked);
                if (in_array($locked->status, ['pending', 'in_progress'], true)) {
                    $oldStatus = $locked->status;
                    $locked->update(['status' => 'open', 'resolved_at' => null]);
                    $activityLogger->record('ticket.status_changed', 'Ticket #' . $locked->id . ' reopened after customer reply.', $locked, ['old' => $oldStatus, 'new' => 'open']);
                }
            });
        } catch (Throwable $exception) {
            if ($attachmentPath) Storage::disk('private')->delete($attachmentPath);
            throw $exception;
        }

        return redirect()
            ->route('customer.dashboard', ['tab' => 'help', 'open_drawer' => 1, 'ticket' => $ticket->id])
            ->with('success', 'Reply sent.');
    }
}
