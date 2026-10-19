<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_duplicate_names_are_preserved_and_disambiguated_on_migration(): void
    {
        // This entire test uses phpunit.xml's disposable SQLite database.
        $migration = require database_path('migrations/2026_10_19_100000_catalog_identity_and_indexes.php');
        $migration->down();
        $ids = [];
        foreach (['Legacy Soap', ' legacy   SOAP '] as $name) {
            $ids[] = DB::table('inventories')->insertGetId(['name' => $name, 'category' => 'home_care', 'stock_balance' => 7]);
        }
        $migration->up();
        $items = DB::table('inventories')->whereIn('id', $ids)->get();
        $this->assertCount(2, $items);
        $this->assertSame(14, (int) $items->sum('stock_balance'));
        $this->assertSame(2, $items->pluck('name_key')->unique()->count());
        $this->assertSame('legacy soap', $items->first()->name_key);
    }
}
