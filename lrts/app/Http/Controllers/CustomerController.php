<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Inventory;
use App\Models\Receipt; // Add this import

class CustomerController extends Controller
{
    public function index()
    {
        $receipts = Auth::user()->receipts()->with('items')->latest()->get();
        $products = Inventory::orderBy('name')->get();
        
        return view('customer.dashboard', compact('receipts', 'products'));
    }

    // Add this new method to handle the web form submission
    public function submitOrder(Request $request)
    {
        // 1. Validate the incoming data, including the hidden items array
        $request->validate([
            'salesman_order_number' => 'required|string|unique:receipts,salesman_order_number',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'You must add at least one product to your item listbox.',
        ]);

        // 2. Create the parent receipt
        $receipt = Receipt::create([
            'user_id' => Auth::id(),
            'salesman_order_number' => $request->salesman_order_number,
            'status' => 'pending',
        ]);

        // 3. Loop through the hidden array items and save them
        foreach ($request->items as $item) {
            $receipt->items()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => 0, // Default to 0 since web UI doesn't ask for price
            ]);
        }

        // 4. Redirect back with a success message
        return redirect()->route('customer.dashboard')->with('success', 'Order submitted successfully! It is now pending admin review.');
    }
}