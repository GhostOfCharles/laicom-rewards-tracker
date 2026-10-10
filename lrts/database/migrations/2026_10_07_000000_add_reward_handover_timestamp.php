<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->dropUnique('earned_rewards_claim_code_unique');
            $table->index('claim_code', 'earned_rewards_claim_code_index');
            $table->timestamp('released_at')->nullable()->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->dropIndex('earned_rewards_claim_code_index');
            $table->dropColumn('released_at');
        });
    }
};
