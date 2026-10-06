<?php

namespace App\Http\Controllers;

use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function customer(Request $request, TicketReply $reply)
    {
        abort_unless(! $reply->is_internal_note && $reply->ticket()->where('user_id', $request->user()->id)->exists(), 403);
        return $this->respond($reply);
    }

    public function admin(TicketReply $reply)
    {
        return $this->respond($reply);
    }

    private function respond(TicketReply $reply)
    {
        abort_unless($reply->attachment_path && Storage::disk('private')->exists($reply->attachment_path), 404);
        return Storage::disk('private')->response($reply->attachment_path);
    }
}
