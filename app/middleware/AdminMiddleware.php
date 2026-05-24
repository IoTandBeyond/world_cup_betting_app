<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\Auth;

class AdminMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }

        if (!Auth::isAdmin()) {
            http_response_code(403);
            die('403 Forbidden');
        }

        if (
            Auth::mustChangePassword()
            && request_path() !== '/password/change'
        ) {
            header('Location: ' . url('/password/change'));
            exit;
        }
    }
}
