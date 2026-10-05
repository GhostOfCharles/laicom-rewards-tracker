<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premium_products', function (Blueprint $table) {
            $table->boolean('is_perishable')->default(false)->after('category');
            $table->date('entry_date')->nullable()->after('is_perishable');
            $table->date('expiry_date')->nullable()->after('entry_date');
        });
    }

    public function down(): void
    {
        Schema::table('premium_products', function (Blueprint $table) {
            $table->dropColumn(['is_perishable', 'entry_date', 'expiry_date']);
        });
    }
};
