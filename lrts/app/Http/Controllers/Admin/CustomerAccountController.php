<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerAccountController extends Controller
{
    public function index(Request $r)
    {
        $search = $r->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $customers = User::where('role', 'customer')->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.customers', compact('customers', 'search'));
    }

    public function temporaryPassword(Request $r, User $customer, ActivityLogger $log)
    {
        abort_unless($customer->role === 'customer', 404);
        $data = $r->validate(['password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]]);
        DB::transaction(function () use ($customer, $data, $log) {
            $customer->password = Hash::make($data['password']);
            $customer->must_change_password = true;
            $customer->save();
            $customer->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $customer->id)->delete();
            }
            $log->record('account.temporary_password_issued', 'Administrator issued a temporary customer password.', $customer);
        });

        return back()->with('success','Temporary password set. Share it privately after verifying the customer identity. They must change it on sign in.');
    }
}
