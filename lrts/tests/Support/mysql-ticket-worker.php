<?php

use App\Http\Controllers\Admin\TicketController;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = getenv('RTS_MYSQL_TEST_DATABASE');
if (! $database || ! preg_match('/^rts_test_[a-z0-9_]+$/', $database)) {
    exit(3);
}
config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database]);
DB::purge('mysql');
try {
    $ticket = Ticket::findOrFail($argv[2]);
    $user = User::findOrFail($argv[3]);
    abort_unless($user->role === 'admin', 403);
    Auth::setUser($user);
    $controller = app(TicketController::class);
    if ($argv[1] === 'archive') {
        $controller->archive($ticket, app(ActivityLogger::class));
    } elseif ($argv[1] === 'reopen') {
        $request = Request::create('/admin/tickets/'.$ticket->id.'/status', 'POST', ['status' => 'open']);
        $controller->updateStatus($request, $ticket, app(ActivityLogger::class));
    } else {
        exit(3);
    }
    echo 'ok';
} catch (ValidationException $exception) {
    echo 'conflict';
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception));
    exit(2);
}
