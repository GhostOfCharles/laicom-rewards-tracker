<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ReceiptSlipController extends Controller
{
    public function customer(Request $request, Receipt $receipt)
    {
        Gate::authorize('view', $receipt);

        return $this->respond($receipt);
    }

    public function admin(Receipt $receipt)
    {
        return $this->respond($receipt);
    }

    private function respond(Receipt $receipt)
    {
        abort_unless($receipt->slip_path && Storage::disk('private')->exists($receipt->slip_path), 404);

        return Storage::disk('private')->response($receipt->slip_path);
    }
}
