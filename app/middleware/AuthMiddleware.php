<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\Auth;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }

        $path = request_path();

        if (
            Auth::mustChangePassword()
            && $path !== '/password/change'
        ) {
            header('Location: ' . url('/password/change'));
            exit;
        }
    }
}
