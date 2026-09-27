<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->string('claim_status', 20)->default('unclaimed')->change();
        });
    }

    public function down(): void
    {
        Schema::table('earned_rewards', function (Blueprint $table) {
            $table->enum('claim_status', ['pending', 'approved', 'claimed', 'rejected'])->default('pending')->change();
        });
    }
};
