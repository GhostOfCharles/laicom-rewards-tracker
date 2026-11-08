<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(string $status = 'closed'): Ticket
    {
        return Ticket::create(['user_id' => User::factory()->create()->id, 'subject' => 'Old support inquiry', 'category' => 'orders', 'status' => $status]);
    }

    public function test_archive_is_admin_only_and_only_closed_tickets_are_eligible(): void
    {
        $ticket = $this->ticket();
        $this->post(route('admin.tickets.archive', $ticket))->assertRedirect(route('login.admin'));
        $this->actingAs($ticket->user)->post(route('admin.tickets.archive', $ticket))->assertForbidden();
        $this->post(route('admin.tickets.restore', $ticket))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['open', 'pending', 'in_progress', 'resolved'] as $status) {
            $active = $this->ticket($status);
            $this->postJson(route('admin.tickets.archive', $active))->assertUnprocessable();
            $this->assertNull($active->fresh()->archived_at);
        }
    }

    public function test_archive_and_restore_preserve_history_attachments_and_audit_idempotently(): void
    {
        Storage::fake('private');
        $ticket = $this->ticket();
        $receipt = Receipt::factory()->create(['user_id' => $ticket->user_id]);
        $ticket->update(['related_receipt_id' => $receipt->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $reply = $ticket->replies()->create(['user_id' => $admin->id, 'body' => 'Public support history', 'is_internal_note' => false, 'attachment_path' => 'ticket-attachments/history.png']);
        $ticket->replies()->create(['user_id' => $admin->id, 'body' => 'Private internal note', 'is_internal_note' => true]);
        Storage::disk('private')->put($reply->attachment_path, 'image');

        $this->actingAs($admin)->post(route('admin.tickets.archive', $ticket))->assertRedirect(route('admin.tickets', ['status' => 'closed']));
        $this->post(route('admin.tickets.archive', $ticket))->assertRedirect();
        $this->assertNotNull($ticket->fresh()->archived_at);
        $this->assertSame($admin->id, $ticket->fresh()->archived_by);
        $this->assertSame(1, ActivityLog::where('action', 'ticket.archived')->count());
        $this->assertSame(2, $ticket->replies()->count());
        $this->assertSame($receipt->id, $ticket->fresh()->related_receipt_id);
        $this->get(route('admin.tickets'))->assertDontSee('Old support inquiry');
        $this->get(route('admin.tickets', ['status' => 'archived', 'search' => 'Old support']))->assertSee('Old support inquiry')->assertSee('RESTORE TICKET');
        $this->get(route('admin.tickets', ['ticket' => $ticket->id]))->assertRedirect(route('admin.tickets', ['status' => 'archived', 'ticket' => $ticket->id]));
        $this->postJson(route('admin.tickets.status', $ticket), ['status' => 'open'])->assertUnprocessable();
        $this->postJson(route('admin.tickets.reply', $ticket), ['body' => 'A stale administrator reply'])->assertUnprocessable();

        $this->actingAs($ticket->user)->get(route('customer.dashboard'))->assertSee('Public support history')->assertDontSee('Private internal note');
        $this->get(route('customer.tickets.attachment', $reply))->assertOk();
        $this->getJson(route('customer.tickets.replies', $ticket))->assertSee('Public support history')->assertDontSee('Private internal note');
        $this->actingAs(User::factory()->create())->get(route('customer.tickets.attachment', $reply))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.tickets.restore', $ticket))->assertRedirect(route('admin.tickets', ['status' => 'closed', 'ticket' => $ticket->id]));
        $this->post(route('admin.tickets.restore', $ticket))->assertRedirect();
        $this->assertNull($ticket->fresh()->archived_at);
        $this->assertNull($ticket->fresh()->archived_by);
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertSame(1, ActivityLog::where('action', 'ticket.restored')->count());
        Storage::disk('private')->assertExists($reply->attachment_path);
    }

    public function test_closed_ticket_can_reopen_and_archiving_rechecks_current_status(): void
    {
        $ticket = $this->ticket();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.tickets', ['ticket' => $ticket->id]))
            ->assertSee('REOPEN TICKET')->assertSee('ARCHIVE TICKET')->assertSee('Archive this closed ticket?');
        $this->postJson(route('admin.tickets.reply', $ticket), ['body' => 'Cannot reply before reopening'])->assertUnprocessable();
        $this->post(route('admin.tickets.status', $ticket), ['status' => 'open'])->assertRedirect();
        $this->postJson(route('admin.tickets.archive', $ticket))->assertUnprocessable();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->actingAs($ticket->user)->post(route('customer.tickets.reply', $ticket), ['body' => 'Customer can reply after reopening'])->assertSessionHasNoErrors();
    }
}
