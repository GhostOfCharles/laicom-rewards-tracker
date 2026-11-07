<?php

namespace Tests\Feature;

use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('mysql')]
class MySqlConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $database = getenv('RTS_MYSQL_TEST_DATABASE');
        if (! $database || ! preg_match('/^rts_test_[a-z0-9_]+$/', $database)) {
            $this->markTestSkipped('Requires a dedicated rts_test_* MySQL database.');
        }
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        // Forward-only migrations; never resets even the dedicated test database.
        Artisan::call('migrate', ['--force' => true]);
    }

    private function fixture(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $receipt = Receipt::factory()->create();
        $item = Inventory::factory()->create();
        $product = PremiumProduct::factory()->create(['stock' => 10]);
        $promotion = Promotion::factory()->create(['premium_product_id' => $product->id, 'buy_product_name' => $item->name]);
        $receipt->items()->create(['product_name' => $item->name, 'quantity' => 2, 'unit_price' => 0]);

        return [$admin, $receipt, $product, $promotion];
    }

    private function parallel(array $jobs): void
    {
        $connection = config('database.connections.mysql');
        $processes = [];
        foreach ($jobs as [$action,$receipt,$user]) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/mysql-worker.php'), $action, (string) $receipt->id, (string) $user->id], base_path(), [
                'RTS_MYSQL_TEST_DATABASE' => DB::getDatabaseName(), 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => DB::getDatabaseName(),
                'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => (string) $connection['password'], 'DB_URL' => '', 'APP_ENV' => 'testing', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array',
            ]);
            $process->setTimeout(30);
            $process->start();
            $processes[] = $process;
        }
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), 'Concurrency worker failed: '.$process->getErrorOutput());
        }
    }

    public function test_parallel_approval_reserves_stock_only_once(): void
    {
        [$admin,$receipt,$product] = $this->fixture();
        $this->parallel([['approve', $receipt, $admin], ['approve', $receipt, $admin]]);
        $this->assertSame('approved', $receipt->fresh()->status);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame(1, $receipt->earnedRewards()->count());
    }

    public function test_claim_and_release_use_a_compatible_lock_order(): void
    {
        [$admin,$receipt,$product,$promotion] = $this->fixture();
        $receipt->update(['status' => 'approved']);
        $reward = EarnedReward::factory()->create(['user_id' => $receipt->user_id, 'receipt_id' => $receipt->id, 'premium_product_id' => $product->id, 'promotion_id' => $promotion->id]);
        $this->parallel([['claim', $receipt, $receipt->user], ['release', $receipt, $admin]]);
        $this->assertSame('released', $reward->fresh()->claim_status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_database_enforces_nonnegative_stock(): void
    {
        $product = PremiumProduct::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('premium_products')->where('id', $product->id)->update(['stock' => -1]);
    }
}
