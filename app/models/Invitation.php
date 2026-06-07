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
        string $expiresAt,
        ?int $tournamentId = null
    ): int {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO invitations (email, token, invited_by, tournament_id, expires_at)
            VALUES (:email, :token, :invited_by, :tournament_id, :expires_at)
        ');

        $stmt->execute([
            'email' => strtolower(trim($email)),
            'token' => $token,
            'invited_by' => $invitedBy,
            'tournament_id' => $tournamentId,
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
            SELECT i.*, u.name AS invited_by_name, t.name AS tournament_name
            FROM invitations i
            JOIN users u ON u.id = i.invited_by
            LEFT JOIN tournaments t ON t.id = i.tournament_id
            ORDER BY i.created_at DESC
        ')->fetchAll();
    }

    public static function forTournament(int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT i.*, u.name AS invited_by_name, t.name AS tournament_name
            FROM invitations i
            JOIN users u ON u.id = i.invited_by
            LEFT JOIN tournaments t ON t.id = i.tournament_id
            WHERE i.tournament_id = :tournament_id
            ORDER BY i.created_at DESC
        ');

        $stmt->execute(['tournament_id' => $tournamentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function isValid(?array $invitation): bool
    {
        if (!$invitation || $invitation['used_at']) {
            return false;
        }

        return strtotime($invitation['expires_at']) > time();
    }

    /** Link from email: valid while not expired (even after account was created). */
    public static function allowsLoginAssist(?array $invitation): bool
    {
        if (!$invitation) {
            return false;
        }

        return strtotime($invitation['expires_at']) > time();
    }
}
