<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function index()
    {
        // Fetch all receipts, including the user who submitted it and the logged items
        $receipts = Receipt::with(['user', 'items'])->orderBy('created_at', 'desc')->get();

        return view('admin.receipts', compact('receipts'));
    }
}