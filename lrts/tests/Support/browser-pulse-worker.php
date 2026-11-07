<?php

use App\Models\CustomerNotification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = trim(file_get_contents(sys_get_temp_dir().'/rts-isolated-mysql-name.txt'));
if (! preg_match('/^rts_test_[a-z0-9_]+$/', $database)) {
    exit(3);
}
config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database]);
DB::purge('mysql');
$config = json_decode(file_get_contents(sys_get_temp_dir().'/rts-browser-config.json'), true);
$ticket = Ticket::findOrFail($config['ticket_id']);
$admin = User::where('email', $config['admin'])->firstOrFail();
$body = 'Live support update '.bin2hex(random_bytes(4));
$ticket->replies()->create(['user_id' => $admin->id, 'body' => $body, 'is_internal_note' => false]);
$ticket->replies()->create(['user_id' => $admin->id, 'body' => 'Never expose this live internal note.', 'is_internal_note' => true]);
CustomerNotification::create(['user_id' => $ticket->user_id, 'type' => 'ticket.replied', 'title' => 'Support replied', 'body' => $body, 'created_at' => now()]);
echo $body;
