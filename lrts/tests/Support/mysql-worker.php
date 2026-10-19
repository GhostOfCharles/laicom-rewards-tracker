<?php

use App\Http\Controllers\Admin\ClaimController;
use App\Models\Receipt;
use App\Models\User;
use App\Services\ClaimService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Test-only process worker. Never accepts a populated application database.
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
    $receipt = Receipt::findOrFail($argv[2]);
    $user = User::findOrFail($argv[3]);
    Auth::setUser($user);
    if ($argv[1] === 'approve') {
        $request = Request::create('/admin/receipts/'.$receipt->id.'/approve', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        app()->call([app(ClaimController::class), 'approve'], ['request' => $request, 'id' => $receipt->id]);
    } elseif ($argv[1] === 'claim') {
        app(ClaimService::class)->requestClaim($receipt, $user);
    } elseif ($argv[1] === 'release') {
        app(ClaimService::class)->release($receipt, $user);
    } else {
        exit(3);
    }
    echo 'ok';
} catch (QueryException $e) {
    fwrite(STDERR, 'Database concurrency error');
    exit(2);
} catch (ValidationException|RuntimeException $e) {
    echo 'conflict';
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e));
    exit(2);
}
