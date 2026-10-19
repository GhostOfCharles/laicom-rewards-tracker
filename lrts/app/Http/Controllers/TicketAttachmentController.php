<?php

namespace App\Http\Controllers;

use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function customer(Request $request, TicketReply $reply)
    {
        abort_if($reply->is_internal_note, 403);
        Gate::authorize('view', $reply->ticket);

        return $this->respond($reply);
    }

    public function admin(TicketReply $reply)
    {
        return $this->respond($reply);
    }

    private function respond(TicketReply $reply)
    {
        abort_unless($reply->attachment_path && Storage::disk('private')->exists($reply->attachment_path), 404);

        return strtolower(pathinfo($reply->attachment_path, PATHINFO_EXTENSION)) === 'pdf'
            ? Storage::disk('private')->download($reply->attachment_path, 'attachment.pdf')
            : Storage::disk('private')->response($reply->attachment_path);
    }
}
