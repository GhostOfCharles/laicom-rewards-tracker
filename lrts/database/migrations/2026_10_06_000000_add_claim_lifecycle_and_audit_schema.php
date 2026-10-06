<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique(['salesman_order_number']);
            $table->index('salesman_order_number');
            $table->string('status')->default('pending')->change();
            $table->date('order_date')->nullable();
            $table->string('slip_path')->nullable();
            $table->string('slip_hash', 64)->nullable()->index();
            $table->string('customer_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        DB::table('receipts')->whereNull('submitted_at')->update(['submitted_at' => DB::raw('created_at')]);
        DB::table('receipts')->where('status', 'claimed')->update(['status' => 'approved']);
        DB::table('receipts')->orderBy('id')->select(['id', 'salesman_order_number'])->chunk(500, function ($receipts) {
            foreach ($receipts as $receipt) {
                $normalized = mb_strtoupper((string) preg_replace('/\s+/u', '', trim($receipt->salesman_order_number)));
                if ($normalized !== $receipt->salesman_order_number) {
                    DB::table('receipts')->where('id', $receipt->id)->update(['salesman_order_number' => $normalized]);
                }
            }
        });

        Schema::create('receipt_order_guards', function (Blueprint $table) {
            $table->char('order_number_hash', 64)->primary();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->string('claim_status', 24)->default('unclaimed')->change();
            $table->string('claim_code', 20)->nullable()->unique();
            $table->timestamp('claim_requested_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('release_note', 255)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('promotion_title')->nullable();
            $table->string('reward_product_name')->nullable();
            $table->string('buy_product_name')->nullable();
            $table->unsignedInteger('purchased_quantity')->nullable();
            $table->unsignedInteger('promo_required_quantity')->nullable();
            $table->unsignedInteger('promo_reward_quantity')->nullable();
        });

        DB::table('earned_rewards')->whereIn('claim_status', ['pending', 'approved'])->update(['claim_status' => 'unclaimed']);
        DB::table('earned_rewards')->where('claim_status', 'rejected')->update(['claim_status' => 'voided']);

        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->dropForeign(['premium_product_id']);
            $table->unsignedBigInteger('premium_product_id')->nullable()->change();
            $table->foreign('premium_product_id')->references('id')->on('premium_products')->nullOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 40)->default('system');
            $table->string('action')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 500);
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::create('premium_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('premium_product_id')->nullable()->constrained('premium_products')->nullOnDelete();
            $table->string('type', 32);
            $table->integer('quantity');
            $table->unsignedInteger('balance_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['premium_product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        DB::table('premium_products')->orderBy('id')->select(['id', 'stock'])->chunk(500, function ($products) {
            $createdAt = now();
            $openingMovements = $products->filter(fn ($product) => (int) $product->stock > 0)->map(fn ($product) => [
                'premium_product_id' => $product->id,
                'type' => 'opening_balance',
                'quantity' => (int) $product->stock,
                'balance_after' => (int) $product->stock,
                'reference_type' => null,
                'reference_id' => null,
                'user_id' => null,
                'notes' => 'Opening stock balance recorded when the premium stock ledger was introduced.',
                'created_at' => $createdAt,
            ])->all();
            if ($openingMovements !== []) {
                DB::table('premium_stock_movements')->insert($openingMovements);
            }
        });

        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('title', 160);
            $table->string('body', 500);
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
        Schema::dropIfExists('premium_stock_movements');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('receipt_order_guards');

        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->dropForeign(['released_by']);
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['selected_by']);
            $table->dropForeign(['premium_product_id']);
            $table->dropColumn([
                'claim_code', 'claim_requested_at', 'released_by', 'release_note', 'voided_at', 'expired_at', 'voided_by',
                'void_reason', 'expires_at', 'selected_by', 'promotion_title', 'reward_product_name', 'buy_product_name',
                'purchased_quantity', 'promo_required_quantity', 'promo_reward_quantity',
            ]);
            $table->unsignedBigInteger('premium_product_id')->nullable(false)->change();
            $table->foreign('premium_product_id')->references('id')->on('premium_products')->cascadeOnDelete();
            $table->enum('claim_status', ['pending', 'approved', 'claimed', 'rejected'])->default('pending')->change();
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['salesman_order_number']);
            $table->dropIndex(['slip_hash']);
            $table->dropColumn(['order_date', 'slip_path', 'slip_hash', 'customer_note', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'cancelled_at']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'claimed'])->default('pending')->change();
            $table->unique('salesman_order_number');
        });
    }
};
