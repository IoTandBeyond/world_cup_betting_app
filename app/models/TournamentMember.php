<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class TournamentMember
{
    public static function add(int $tournamentId, int $userId): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT IGNORE INTO tournament_members (tournament_id, user_id)
            VALUES (:tournament_id, :user_id)
        ');

        $stmt->execute([
            'tournament_id' => $tournamentId,
            'user_id' => $userId,
        ]);
    }

    public static function isMember(int $userId, int $tournamentId): bool
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT 1 FROM tournament_members
            WHERE user_id = :user_id AND tournament_id = :tournament_id
            LIMIT 1
        ');

        $stmt->execute([
            'user_id' => $userId,
            'tournament_id' => $tournamentId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<int> */
    public static function tournamentIdsForUser(int $userId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT tournament_id FROM tournament_members
            WHERE user_id = :user_id
            ORDER BY joined_at ASC
        ');

        $stmt->execute(['user_id' => $userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return list<array> */
    public static function tournamentsForUser(int $userId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT t.*
            FROM tournaments t
            JOIN tournament_members tm ON tm.tournament_id = t.id
            WHERE tm.user_id = :user_id
            ORDER BY FIELD(t.status, "active", "upcoming", "finished"), t.start_date DESC
        ');

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array> */
    public static function usersForTournament(int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT u.id, u.name, u.email, u.role, u.is_active,
                   u.must_change_password, u.policy_accepted_at, u.policy_version,
                   u.last_login_at, u.created_at, tm.joined_at
            FROM tournament_members tm
            JOIN users u ON u.id = tm.user_id
            WHERE tm.tournament_id = :tournament_id
              AND u.role = "user"
            ORDER BY u.name ASC
        ');

        $stmt->execute(['tournament_id' => $tournamentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
