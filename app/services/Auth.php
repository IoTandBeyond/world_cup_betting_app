<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class Auth
{
    public static function attempt(
        string $email,
        string $password
    ): bool {
        $user = User::findByEmail($email);

        if (!$user || !(int) $user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        session_regenerate_id(true);

        User::updateLastLogin((int) $user['id']);

        return true;
    }

    public static function mustChangePassword(): bool
    {
        $user = self::user();

        return $user && (int) ($user['must_change_password'] ?? 0) === 1;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user && $user['role'] === 'admin';
    }

    public static function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return User::findById((int) $_SESSION['user_id']);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
