<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private const CATEGORIES = ['auth', 'receipts', 'rewards', 'premium', 'promotions', 'inventory', 'tickets'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'user_id' => 'nullable|integer|exists:users,id',
            'category' => 'nullable|in:' . implode(',', self::CATEGORIES),
            'q' => 'nullable|string|max:100',
        ]);

        $query = ActivityLog::with('user')
            ->when($validated['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($validated['category'] ?? null, function ($query, $category) {
                $prefix = ['receipts' => 'receipt', 'rewards' => 'reward'][$category] ?? $category;
                $query->where('action', 'like', $prefix . '.%');
            })
            ->when(trim($validated['q'] ?? ''), function ($query, $term) {
                $query->where(fn ($q) => $q->where('description', 'like', '%' . $term . '%')->orWhere('action', 'like', '%' . $term . '%'));
            })
            ->latest('created_at');

        $logs = $query->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'role']);
        $categories = self::CATEGORIES;

        return view('admin.activity-log', compact('logs', 'users', 'categories', 'validated'));
    }
}
