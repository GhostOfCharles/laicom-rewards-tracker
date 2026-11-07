<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LoginProtection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request, ActivityLogger $activityLogger)
    {
        LoginProtection::validate($request);
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            LoginProtection::failed($request);
            $activityLogger->recordAs(null, 'auth.login_failed', 'API login attempt failed.', null, ['email' => $credentials['email']]);

            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        abort_if($user->must_change_password, 403, 'Change your temporary password in the web application first.');
        abort_if($user->role !== 'customer' && ! config('lrts.allow_admin_api'), 403, 'Mobile API access is restricted to customer accounts.');
        LoginProtection::clear($request);
        $token = DB::transaction(function () use ($user, $activityLogger) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->tokens()->where('name', 'flutter-mobile-app')->delete();
            $token = $user->createToken('flutter-mobile-app', ['receipts:create', 'user:read'], now()->addDays(60))->plainTextToken;
            $activityLogger->recordAs($user, 'auth.login', 'User signed in through the mobile API.', $user, ['client' => 'flutter-mobile-app']);

            return $token;
        });

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }
}
