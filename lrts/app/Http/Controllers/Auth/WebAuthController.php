<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LoginProtection;
use EduLazaro\Laracaptcha\Facades\Captcha as CaptchaFacade;
use EduLazaro\Laracaptcha\Rules\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class WebAuthController extends Controller
{
    // Show Customer Register Form
    public function showRegister()
    {
        return view('auth.customer-register');
    }

    // Handle Customer Registration
    public function register(Request $request, ActivityLogger $activityLogger)
    {
        $request->validate(['phone_number' => 'required|string']);
        $request->merge([
            'phone_number' => preg_replace('/\D/', '', (string) $request->phone_number),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'store_name' => 'required|string|max:255',
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'password' => [
                'required',
                'confirmed',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            CaptchaFacade::responseField() => ['required', new Captcha],
        ]);

        $user = DB::transaction(function () use ($request, $activityLogger) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'store_name' => $request->store_name,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'role' => 'customer',
            ]);
            $activityLogger->recordAs($user, 'auth.register', 'Customer account registered.', $user, ['email' => $user->email]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        // Customers land on their own dashboard
        return redirect()->route('customer.dashboard')->with('success', 'Account created successfully. Welcome to RTS!');
    }

    // Handle Login for both Customer and Admin
    public function login(Request $request, ActivityLogger $activityLogger)
    {
        LoginProtection::validate($request);
        $credentials = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:4096',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if ($request->routeIs('login.admin.submit') && Auth::user()->role !== 'admin') {
                $activityLogger->recordAs(Auth::user(), 'auth.login_failed', 'Admin login denied for a non-admin account.', Auth::user(), ['email' => $credentials['email']]);
                Auth::logout();
                LoginProtection::failed($request);

                return back()->withErrors([
                    'email' => 'This account does not have administrator access.',
                ])->onlyInput('email');
            }

            LoginProtection::clear($request);
            $activityLogger->record('auth.login', 'User logged in.', Auth::user(), ['login_route' => $request->routeIs('login.admin.submit') ? 'admin' : 'customer']);

            // Redirect based on user role
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            // Customers land on their own dashboard
            return redirect()->route('customer.dashboard');
        }

        LoginProtection::failed($request);
        $activityLogger->recordAs(null, 'auth.login_failed', 'Login attempt failed.', null, ['email' => $credentials['email']]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // Handle Logout
    public function logout(Request $request, ActivityLogger $activityLogger)
    {
        $user = Auth::user();
        $activityLogger->recordAs($user, 'auth.logout', 'User logged out.', $user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal');
    }
}
