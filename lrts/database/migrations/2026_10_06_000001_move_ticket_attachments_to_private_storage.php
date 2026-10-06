<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ticket_replies')->whereNotNull('attachment_path')->orderBy('id')->chunk(100, function ($replies) {
            foreach ($replies as $reply) {
                $path = ltrim((string) $reply->attachment_path, '/');
                if (! Storage::disk('public')->exists($path)) {
                    continue;
                }
                $contents = Storage::disk('public')->get($path);
                Storage::disk('private')->put($path, $contents);
                DB::table('ticket_replies')->where('id', $reply->id)->update(['attachment_path' => $path]);
                Storage::disk('public')->delete($path);
            }
        });
    }

    public function down(): void
    {
        // Keep attachments private on rollback; copying them back to public storage could expose later uploads.
    }
};
