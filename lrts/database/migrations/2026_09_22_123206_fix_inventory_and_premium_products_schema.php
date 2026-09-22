<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add stock to Premium Products
        Schema::table('premium_products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('name');
        });

        // 2. Refactor the Inventories table to hold Unilever products instead of premium stock
        Schema::table('inventories', function (Blueprint $table) {
            // Drop the old foreign key that linked it to premium products
            $table->dropForeign(['premium_product_id']);
            $table->dropColumn('premium_product_id');
            
            // Add the correct columns for Unilever products
            $table->string('name')->after('id');
            $table->string('category')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('premium_products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('premium_product_id')->constrained()->onDelete('cascade');
            $table->dropColumn(['name', 'category']);
        });
    }
};