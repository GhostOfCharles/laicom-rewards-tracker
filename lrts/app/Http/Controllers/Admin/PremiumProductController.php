<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PremiumProduct;
use Illuminate\Http\Request;

class PremiumProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming data
        $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'required|string|unique:premium_products,item_code',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        // 2. Handle the file upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Stores the file in storage/app/public/products and returns the path
            $imagePath = $request->file('image')->store('products', 'public');
        }

        // 3. Save to the Premium Products table
        PremiumProduct::create([
            'name' => $request->name,
            'item_code' => $request->item_code,
            'image_path' => $imagePath,
        ]);

        // 4. Redirect back with a success message
        return redirect()->route('admin.dashboard')->with('success', 'Premium product added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}