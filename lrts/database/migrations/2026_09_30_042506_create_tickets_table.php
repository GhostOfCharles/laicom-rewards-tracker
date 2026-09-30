<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 200);
            $table->string('category', 50)->default('other'); // orders|rewards|promos|account|other
            $table->string('status', 20)->default('open');    // open|pending|in_progress|resolved|closed
            $table->string('priority', 20)->default('normal'); // low|normal|high
            $table->foreignId('related_receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};