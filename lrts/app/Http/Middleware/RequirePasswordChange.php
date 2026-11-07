<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePasswordChange
{
    public function handle(Request $r, Closure $next)
    {
        if ($r->user()?->must_change_password && ! $r->routeIs('customer.profile', 'customer.password', 'logout')) {
            return $r->expectsJson() ? response()->json(['message' => 'Change your temporary password in Account settings before continuing.'], 403) : redirect()->route('customer.profile')->with('success', 'Please replace your temporary password before continuing.');
        }

        return $next($r);
    }
}
