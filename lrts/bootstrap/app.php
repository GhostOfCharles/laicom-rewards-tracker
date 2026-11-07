<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCustomer;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $proxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        if ($proxies) {
            $middleware->trustProxies(at: $proxies);
        }
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'customer' => EnsureUserIsCustomer::class,
            'password.current' => RequirePasswordChange::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('admin/*')
                ? route('login.admin')
                : route('login.customer');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            $headers = $exception->getHeaders();
            $message = 'Too many requests. Please wait and try again.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 429, $headers)
                : response()->view('errors.429', ['retryAfter' => $headers['Retry-After'] ?? 60], 429, $headers);
        });
        $exceptions->render(function (QueryException $exception, Request $request) {
            if ($exception instanceof UniqueConstraintViolationException) {
                $field = str_contains($exception->getSql(), 'inventories') ? 'name' : (str_contains($exception->getSql(), 'users') ? 'email' : 'error');
                $message = 'This record already exists. Refresh and try again.';

                return $request->expectsJson() ? response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422) : back()->withErrors([$field => $message]);
            }
            if (! in_array((string) $exception->getCode(), ['40001']) && ! in_array($exception->errorInfo[1] ?? null, [1205, 1213])) {
                return null;
            }

            return $request->expectsJson() ? response()->json(['message' => 'This record is busy. Please try again.'], 409) : back()->withErrors(['error' => 'This record is busy. Please try again.']);
        });
    })->create();
