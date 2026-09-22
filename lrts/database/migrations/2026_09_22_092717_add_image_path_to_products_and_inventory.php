<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premium_products', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('name');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('reserved_stock');
        });
    }

    public function down(): void
    {
        Schema::table('premium_products', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};