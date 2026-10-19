<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function profile(Request $r)
    {
        return view('customer.profile', ['user' => $r->user()]);
    }

    public function update(Request $r, ActivityLogger $log)
    {
        $r->validate(['phone_number' => 'required|string']);
        $r->merge(['phone_number' => preg_replace('/\D/', '', (string) $r->phone_number)]);
        $data = $r->validate(['name' => 'required|string|max:255', 'store_name' => 'required|string|max:255', 'phone_number' => ['required', 'regex:/^09\d{9}$/']]);
        $r->user()->update($data);
        $log->record('account.updated', 'Customer updated account details.', $r->user());

        return back()->with('success', 'Account updated.');
    }

    public function password(Request $r, ActivityLogger $log)
    {
        $data = $r->validate(['current_password' => 'required|string|current_password:web', 'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]]);
        DB::transaction(function () use ($r, $data, $log) {
            $user = $r->user();
            $user->password = Hash::make($data['password']);
            $user->must_change_password = false;
            $user->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $r->session()->getId())->delete();
            }
            $log->record('account.password_changed', 'Customer changed password.', $user);
        });
        $r->session()->regenerate();

        return back()->with('success', 'Password changed. Other sessions and API tokens were revoked.');
    }

    public function preview(Request $r)
    {
        return view('customer.partials.notification-preview', ['notifications' => $r->user()->customerNotifications()->latest('id')->limit(10)->get()]);
    }

    public function notifications(Request $r)
    {
        return view('customer.notifications', ['notifications' => $r->user()->customerNotifications()->latest('id')->paginate(20)]);
    }

    public function read(Request $r, CustomerNotification $notification)
    {
        abort_unless($notification->user_id === $r->user()->id, 403);
        $notification->update(['read_at' => now()]);
        Cache::forget('rts-pulse:customer:'.$r->user()->id);

        return back()->with('success', 'Notification marked as read.');
    }
}
