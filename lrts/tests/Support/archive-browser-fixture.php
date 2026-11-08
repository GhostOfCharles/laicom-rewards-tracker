<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$directory = realpath(getenv('RTS_ARCHIVE_QA_DIR') ?: '');
if (! $directory || dirname($directory) !== realpath(sys_get_temp_dir()) || ! str_starts_with(basename($directory), 'rts-archive-qa-')) {
    exit(3);
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'sqlite' || realpath(DB::connection()->getDatabaseName()) !== realpath($directory.'/audit.sqlite')) {
    exit(3);
}
Artisan::call('migrate', ['--force' => true]);
$password = bin2hex(random_bytes(16));
$admin = User::factory()->create(['email' => 'archive-admin@example.test', 'name' => 'RTS Test Admin', 'role' => 'admin', 'password' => bcrypt($password)]);
$customer = User::factory()->create(['email' => 'archive-customer@example.test', 'name' => 'RTS Test Customer', 'password' => bcrypt($password)]);
$ticket = Ticket::create(['user_id' => $customer->id, 'subject' => 'Finished support inquiry', 'category' => 'orders', 'status' => 'closed']);
$ticket->replies()->create(['user_id' => $admin->id, 'body' => 'Your previous inquiry has been completed.', 'is_internal_note' => false]);
echo json_encode(['admin' => $admin->email, 'customer' => $customer->email, 'password' => $password, 'ticket_id' => $ticket->id]);
