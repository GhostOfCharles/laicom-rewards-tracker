<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReceiptSubmissionService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function store(Request $request, ReceiptSubmissionService $submissions)
    {
        abort_unless($request->user()->role === 'customer', 403, 'Customer access is required.');

        $receipt = $submissions->createFromRequest($request, $request->user());

        return response()->json([
            'message' => 'Order submitted successfully. Pending admin verification.',
            'receipt' => $receipt->load('items'),
        ], 201);
    }
}
