<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginUserAction
{
    public function execute(
        string $login,
        string $password,
        bool $remember = false
    ): bool {
        $login = trim($login);

        $field = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        $throttleKey = Str::lower($login) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return false;
        }

        $credentials = [
            $field => $login,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 60);
            return false;
        }

        RateLimiter::clear($throttleKey);

        request()->session()->regenerate();

        return true;
    }
}