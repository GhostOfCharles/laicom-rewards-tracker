<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $version = DB::selectOne('SELECT VERSION() AS version')->version;
        if (str_contains($version, 'MariaDB') || version_compare($version, '8.0.16', '<')) {
            return;
        }
        foreach (['premium_products' => 'stock', 'inventories' => 'stock_balance'] as $table => $field) {
            if (DB::table($table)->where($field, '<', 0)->exists()) {
                Log::warning('Nonnegative stock check requires manual reconciliation.', ['table' => $table]);

                continue;
            }
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_nonnegative CHECK ({$field} >= 0)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach (['premium_products', 'inventories'] as $table) {
            $exists = DB::table('information_schema.TABLE_CONSTRAINTS')->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())->where('TABLE_NAME', $table)->where('CONSTRAINT_NAME', $table.'_nonnegative')->exists();
            if ($exists) {
                DB::statement("ALTER TABLE {$table} DROP CHECK {$table}_nonnegative");
            }
        }
    }
};
