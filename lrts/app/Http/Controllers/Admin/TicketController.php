<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotification;
use App\Models\Ticket;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['ticket' => 'nullable|integer|min:1', 'status' => 'nullable|in:all,open,pending,in_progress,resolved,closed,archived']);
        $filter = $request->query('status', 'all');
        $selectedId = $request->query('ticket');

        $query = Ticket::with('user')
            ->withCount('replies')
            ->latest();

        $filter === 'archived' ? $query->archived() : $query->unarchived();

        if (in_array($filter, ['open', 'pending', 'in_progress', 'resolved', 'closed'], true)) {
            $query->where('status', $filter);
        }

        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $query->when($search, fn ($q) => $q->where(fn ($q) => $q->where('subject', 'like', '%'.$search.'%')->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'))));
        $tickets = $query->paginate(20)->withQueryString();

        $selectedTicket = $selectedId
            ? Ticket::with(['user', 'replies.user', 'relatedReceipt'])->find($selectedId)
            : $tickets->first()?->load(['replies.user', 'relatedReceipt']);

        if ($selectedTicket && (bool) $selectedTicket->archived_at !== ($filter === 'archived')) {
            return redirect()->route('admin.tickets', ['status' => $selectedTicket->archived_at ? 'archived' : $selectedTicket->status, 'ticket' => $selectedTicket->id]);
        }

        return view('admin.tickets', compact('tickets', 'selectedTicket', 'filter', 'search'));
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
                if ($locked->archived_at || $locked->status === 'closed') {
                    throw ValidationException::withMessages(['error' => 'Restore and reopen this ticket before replying.']);
                }
                $locked->replies()->create([
                    'user_id' => Auth::id(),
                    'body' => $request->body,
                    'is_internal_note' => $isInternal,
                    'attachment_path' => $attachmentPath,
                ]);
                if ($isInternal) {
                    $activityLogger->record('ticket.internal_note', 'Internal note added to ticket #'.$locked->id.'.', $locked);
                } else {
                    $activityLogger->record('ticket.replied', 'Admin replied to ticket #'.$locked->id.'.', $locked);
                    CustomerNotification::create([
                        'user_id' => $locked->user_id,
                        'type' => 'ticket.replied',
                        'title' => 'Support replied',
                        'body' => 'Support replied to your ticket: '.$locked->subject,
                        'receipt_id' => $locked->related_receipt_id,
                        'created_at' => now(),
                    ]);
                    if (in_array($locked->status, ['open', 'in_progress'], true)) {
                        $oldStatus = $locked->status;
                        $locked->update(['status' => 'pending', 'resolved_at' => null]);
                        $activityLogger->record('ticket.status_changed', 'Ticket #'.$locked->id.' moved to pending after admin reply.', $locked, ['old' => $oldStatus, 'new' => 'pending']);
                    }
                }
            });
        } catch (Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('private')->delete($attachmentPath);
            }
            throw $exception;
        }

        $this->forgetPulse($ticket);

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
            if ($locked->archived_at) {
                throw ValidationException::withMessages(['error' => 'Restore this ticket before changing its status.']);
            }
            $oldStatus = $locked->status;
            $locked->update($updates);
            if ($oldStatus !== $newStatus) {
                $activityLogger->record('ticket.status_changed', 'Ticket #'.$locked->id.' marked as '.strtoupper(str_replace('_', ' ', $newStatus)).'.', $locked, ['old' => $oldStatus, 'new' => $newStatus]);
            }
        });

        $this->forgetPulse($ticket);

        return redirect()
            ->route('admin.tickets', ['ticket' => $ticket->id, 'status' => $request->query('status', 'all')])
            ->with('success', 'Ticket marked as '.strtoupper(str_replace('_', ' ', $newStatus)).'.');
    }

    public function archive(Ticket $ticket, ActivityLogger $activityLogger)
    {
        DB::transaction(function () use ($ticket, $activityLogger) {
            $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            if ($locked->status !== 'closed') {
                throw ValidationException::withMessages(['error' => 'Close this ticket before archiving it.']);
            }
            if (! $locked->archived_at) {
                $locked->update(['archived_at' => now(), 'archived_by' => Auth::id()]);
                $activityLogger->record('ticket.archived', 'Ticket #'.$locked->id.' archived.', $locked);
            }
        }, 3);
        $this->forgetPulse($ticket);

        return redirect()->route('admin.tickets', ['status' => 'closed'])->with('success', 'Ticket archived. Its conversation history is preserved.');
    }

    public function restore(Ticket $ticket, ActivityLogger $activityLogger)
    {
        DB::transaction(function () use ($ticket, $activityLogger) {
            $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            if ($locked->archived_at) {
                $properties = ['archived_at' => $locked->archived_at->toIso8601String(), 'archived_by' => $locked->archived_by];
                $locked->update(['archived_at' => null, 'archived_by' => null]);
                $activityLogger->record('ticket.restored', 'Ticket #'.$locked->id.' restored.', $locked, $properties);
            }
        }, 3);
        $this->forgetPulse($ticket);

        return redirect()->route('admin.tickets', ['status' => $ticket->fresh()->status, 'ticket' => $ticket->id])->with('success', 'Ticket restored.');
    }

    private function forgetPulse(Ticket $ticket): void
    {
        Cache::forget('rts-pulse:admin');
        Cache::forget('rts-pulse:customer:'.$ticket->user_id);
    }
}
