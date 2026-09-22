<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate the incoming JSON payload
        $request->validate([
            'salesman_order_number' => 'required|string|unique:receipts,salesman_order_number',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        // 2. Create the parent receipt record linked to the authenticated user
        $receipt = Receipt::create([
            'user_id' => $request->user()->id,
            'salesman_order_number' => $request->salesman_order_number,
            'status' => 'pending',
        ]);

        // 3. Loop through the products and save them as Receipt Items
        foreach ($request->items as $item) {
            $receipt->items()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? 0,
            ]);
        }

        // 4. Return success response to the mobile app
        return response()->json([
            'message' => 'Order submitted successfully. Pending admin verification.',
            'receipt' => $receipt->load('items') // Loads the items to show in the response
        ], 201);
    }
}