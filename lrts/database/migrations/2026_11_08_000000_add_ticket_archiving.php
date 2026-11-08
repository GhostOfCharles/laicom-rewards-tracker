<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite must disable foreign keys outside a transaction when rebuilding tickets.
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
