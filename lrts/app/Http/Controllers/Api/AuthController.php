<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use EduLazaro\Laracaptcha\Rules\Captcha;
use EduLazaro\Laracaptcha\Facades\Captcha as CaptchaFacade;

class WebAuthController extends Controller
{
    // Show Customer Register Form
    public function showRegister()
    {
        return view('auth.customer-register');
    }

    // Handle Customer Registration
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'store_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50',
            'password' => 'required|string|min:6|confirmed',
            CaptchaFacade::responseField() => ['required', new Captcha],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'store_name' => $request->store_name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ]);

        Auth::login($user);

        // Customers land on their own dashboard
        return redirect()->route('customer.dashboard')->with('success', 'Account created successfully. Welcome to LRTS!');
    }

    // Handle Login for both Customer and Admin
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if ($request->routeIs('login.admin.submit') && Auth::user()->role !== 'admin') {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'This account does not have administrator access.',
                ])->onlyInput('email');
            }

            // Redirect based on user role
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            // Customers land on their own dashboard
            return redirect()->route('customer.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // Handle Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('portal');
    }
}