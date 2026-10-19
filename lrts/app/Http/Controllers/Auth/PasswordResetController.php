<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    private function enabled(): void
    {
        abort_unless(config('lrts.password_reset_enabled') && ! in_array(config('mail.default'), ['log', 'array']), 404);
    }

    public function form()
    {
        $this->enabled();

        return view('auth.forgot-password');
    }

    public function send(Request $r)
    {
        $this->enabled();
        $r->validate(['email' => 'required|email']);
        try {
            Password::sendResetLink($r->only('email'));
        } catch (\Throwable $exception) {
            Log::warning('Password reset delivery unavailable.', ['exception_type' => get_class($exception)]);
        }

return back()->with('success', 'If this account exists, a reset link was requested. Contact support if it does not arrive.');
    }

    public function resetForm(Request $r, string $token)
    {
        $this->enabled();

        return view('auth.reset-password', ['token' => $token, 'email' => $r->query('email')]);
    }

    public function reset(Request $r)
    {
        $this->enabled();
        $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()]]);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->password = Hash::make($password);
            $user->must_change_password = false;
            $user->setRememberToken(Str::random(60));
            $user->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            app(ActivityLogger::class)->recordAs($user, 'auth.password_reset', 'Password reset completed.', $user);
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('login.customer')->with('success', 'Password reset. You can sign in.') : back()->withErrors(['email' => __($status)]);
    }
}
