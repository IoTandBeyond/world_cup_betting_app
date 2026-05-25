<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\Auth;
use App\Services\OnboardingService;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }

        OnboardingService::enforce();
    }
}
