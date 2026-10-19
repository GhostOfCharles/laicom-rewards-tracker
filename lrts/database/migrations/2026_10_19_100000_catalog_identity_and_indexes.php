<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', fn (Blueprint $t) => $t->string('name_key', 255)->nullable());
        $used = [];
        foreach (DB::table('inventories')->orderBy('id')->get() as $item) {
            $name = preg_replace('/\s+/u', ' ', trim($item->name));
            $key = mb_strtolower($name);
            if (isset($used[$key])) {
                $name = mb_substr($name, 0, 220).' (catalog '.$item->id.')';
                $key = mb_strtolower($name);
                while (isset($used[$key])) {
                    $name .= '-';
                    $key = mb_strtolower($name);
                }
                Log::warning('Catalog duplicate disambiguated during migration.', ['inventory_id' => $item->id]);
            }
            DB::table('inventories')->where('id', $item->id)->update(['name' => $name, 'name_key' => $key]);
            $used[$key] = $item->id;
        }
        Schema::table('inventories', fn (Blueprint $t) => $t->unique('name_key'));
        foreach (['promotions' => 'buy_product_name', 'receipt_items' => 'product_name'] as $table => $field) {
            Schema::table($table, fn (Blueprint $t) => $t->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete());
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $field, $used) {
                foreach ($rows as $row) {
                    $key = mb_strtolower(preg_replace('/\s+/u', ' ', trim($row->$field)));
                    if (isset($used[$key])) {
                        DB::table($table)->where('id', $row->id)->update(['inventory_id' => $used[$key]]);
                    }
                }
            });
        }
        Schema::table('receipts', function (Blueprint $t) {
            $t->index('status');
            $t->index(['user_id', 'updated_at']);
        });
        Schema::table('earned_rewards', function (Blueprint $t) {
            $t->index('claim_status');
            $t->index(['receipt_id', 'claim_status']);
        });
        Schema::table('tickets', fn (Blueprint $t) => $t->index('status'));
    }

    public function down(): void
    {
        foreach (['promotions', 'receipt_items'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('inventory_id');
            });
        }
        Schema::table('inventories', function (Blueprint $t) {
            $t->dropUnique(['name_key']);
            $t->dropColumn('name_key');
        });
        Schema::table('receipts', function (Blueprint $t) {
            $t->dropIndex(['status']);
            $t->dropIndex(['user_id', 'updated_at']);
        });
        Schema::table('earned_rewards', function (Blueprint $t) {
            $t->dropIndex(['claim_status']);
            $t->dropIndex(['receipt_id', 'claim_status']);
        });
        Schema::table('tickets', fn (Blueprint $t) => $t->dropIndex(['status']));
    }
};
