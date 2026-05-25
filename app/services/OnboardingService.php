<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class OnboardingService
{
    /** Paths allowed before policy is accepted. */
    private const POLICY_PATHS = ['/policy/accept', '/logout'];

    /** Paths allowed after policy, while temp password is active. */
    private const PASSWORD_PATHS = ['/password/change', '/logout'];

    public static function enforce(): void
    {
        if (!Auth::check()) {
            return;
        }

        $path = request_path();
        $user = Auth::user();

        if (!User::hasAcceptedPolicy($user)) {
            if (!in_array($path, self::POLICY_PATHS, true)) {
                header('Location: ' . url('/policy/accept'));
                exit;
            }

            return;
        }

        if (Auth::mustChangePassword() && !in_array($path, self::PASSWORD_PATHS, true)) {
            header('Location: ' . url('/password/change'));
            exit;
        }
    }

    public static function redirectAfterLogin(): string
    {
        $user = Auth::user();

        if (!User::hasAcceptedPolicy($user)) {
            return '/policy/accept';
        }

        if (Auth::mustChangePassword()) {
            return '/password/change';
        }

        return Auth::isAdmin() ? '/admin' : '/dashboard';
    }
}
