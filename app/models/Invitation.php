<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class Invitation
{
    public static function create(
        string $email,
        string $token,
        int $invitedBy,
        string $expiresAt
    ): int {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO invitations (email, token, invited_by, expires_at)
            VALUES (:email, :token, :invited_by, :expires_at)
        ');

        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'invited_by' => $invitedBy,
            'expires_at' => $expiresAt,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function findByToken(string $token): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM invitations
            WHERE token = :token
            LIMIT 1
        ');

        $stmt->execute(['token' => $token]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function markUsed(int $id): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE invitations SET used_at = NOW() WHERE id = :id
        ');

        $stmt->execute(['id' => $id]);
    }

    public static function all(): array
    {
        $db = Database::connection();

        return $db->query('
            SELECT i.*, u.name AS invited_by_name
            FROM invitations i
            JOIN users u ON u.id = i.invited_by
            ORDER BY i.created_at DESC
        ')->fetchAll();
    }

    public static function isValid(?array $invitation): bool
    {
        if (!$invitation || $invitation['used_at']) {
            return false;
        }

        return strtotime($invitation['expires_at']) > time();
    }
}
