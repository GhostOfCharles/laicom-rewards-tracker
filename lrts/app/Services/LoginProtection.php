<?php

namespace App\Services;

use EduLazaro\Laracaptcha\Facades\Captcha as CaptchaFacade;
use EduLazaro\Laracaptcha\Rules\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginProtection
{
    public static function key(Request $request): string
    {
        $email = $request->input('email');

        return 'login-fail:'.hash('sha256', mb_strtolower(trim(is_string($email) ? $email : 'invalid')).'|'.$request->ip());
    }

    public static function validate(Request $request): void
    {
        if (RateLimiter::attempts(self::key($request)) >= 3) {
            if ($request->hasSession()) {
                $request->session()->flash('login_challenge', true);
            }
            $request->validate([CaptchaFacade::responseField() => ['required', new Captcha]]);
        }
    }

    public static function failed(Request $request): void
    {
        RateLimiter::hit(self::key($request), 600);
        if ($request->hasSession() && RateLimiter::attempts(self::key($request)) >= 3) {
            $request->session()->flash('login_challenge', true);
        }
    }

    public static function clear(Request $request): void
    {
        RateLimiter::clear(self::key($request));
    }
}
