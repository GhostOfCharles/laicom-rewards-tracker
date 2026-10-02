<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->date('entry_date')->nullable()->after('reserved_stock');
            $table->date('expiry_date')->nullable()->after('entry_date');
        });

        $this->normalizeKnownCategories('inventories');
        $this->normalizeKnownCategories('premium_products');
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['entry_date', 'expiry_date']);
        });
    }

    private function normalizeKnownCategories(string $table): void
    {
        $mappings = [
            'home care' => 'home_care',
            'home_care' => 'home_care',
            'home-care' => 'home_care',
            'home' => 'home_care',
            'food' => 'food',
            'personal care' => 'personal_care',
            'personal_care' => 'personal_care',
            'personal-care' => 'personal_care',
            'beauty/personal care' => 'personal_care',
            'beauty and personal care' => 'personal_care',
        ];

        foreach ($mappings as $legacy => $canonical) {
            DB::table($table)
                ->whereRaw('LOWER(TRIM(category)) = ?', [$legacy])
                ->update(['category' => $canonical]);
        }
    }
};
