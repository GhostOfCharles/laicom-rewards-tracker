<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketArchiveMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_forward_migration_preserves_existing_tickets_and_replies(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::getDatabaseName());
        $migration = require database_path('migrations/2026_11_08_000000_add_ticket_archiving.php');
        $migration->down();
        DB::table('migrations')->where('migration', '2026_11_08_000000_add_ticket_archiving')->delete();
        $user = User::factory()->create();
        $ticket = Ticket::create(['user_id' => $user->id, 'subject' => 'Existing ticket', 'category' => 'orders', 'status' => 'closed']);
        $reply = $ticket->replies()->create(['user_id' => $user->id, 'body' => 'Existing conversation', 'is_internal_note' => false]);
        Artisan::call('migrate', ['--force' => true]);
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->archived_at);
        $this->assertNull($ticket->fresh()->archived_by);
        $this->assertSame('Existing conversation', $reply->fresh()->body);
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }
}
