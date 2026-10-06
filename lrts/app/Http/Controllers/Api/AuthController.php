<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request, ActivityLogger $activityLogger)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $activityLogger->recordAs(null, 'auth.login_failed', 'API login attempt failed.', null, ['email' => $credentials['email']]);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = DB::transaction(function () use ($user, $activityLogger) {
            $token = $user->createToken('flutter-mobile-app')->plainTextToken;
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
