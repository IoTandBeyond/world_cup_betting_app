<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prediction;

class LeaderboardService
{
    public static function rebuild(int $tournamentId): void
    {
        $db = Database::connection();

        $db->prepare('
            DELETE FROM leaderboard_cache WHERE tournament_id = :tid
        ')->execute(['tid' => $tournamentId]);

        $stmt = $db->prepare('
            SELECT u.id AS user_id,
                   u.name,
                   COALESCE(mp.match_points, 0)
                       + COALESCE(bp.points_awarded, 0) AS total_points,
                   COALESCE(mp.exact_hits, 0) AS exact_hits
            FROM tournament_members tm
            JOIN users u ON u.id = tm.user_id
            LEFT JOIN (
                SELECT p.user_id,
                       SUM(p.points_awarded) AS match_points,
                       SUM(CASE
                           WHEN p.points_awarded = (
                               SELECT `value` FROM settings
                               WHERE `key` = "points_exact_score" LIMIT 1
                           ) THEN 1 ELSE 0 END) AS exact_hits
                FROM predictions p
                INNER JOIN matches m ON m.id = p.match_id
                    AND m.tournament_id = :tid
                GROUP BY p.user_id
            ) mp ON mp.user_id = u.id
            LEFT JOIN bonus_predictions bp ON bp.user_id = u.id
                AND bp.tournament_id = :tid2
            WHERE tm.tournament_id = :tid3
              AND u.is_active = 1
              AND u.role = "user"
            ORDER BY total_points DESC,
                     exact_hits DESC,
                     u.name ASC
        ');

        $stmt->execute([
            'tid' => $tournamentId,
            'tid2' => $tournamentId,
            'tid3' => $tournamentId,
        ]);

        $rows = $stmt->fetchAll();
        $rank = 1;
        $insert = $db->prepare('
            INSERT INTO leaderboard_cache (
                tournament_id, user_id, total_points, rank_position
            ) VALUES (:tid, :uid, :points, :rank)
        ');

        foreach ($rows as $row) {
            $insert->execute([
                'tid' => $tournamentId,
                'uid' => $row['user_id'],
                'points' => (int) $row['total_points'],
                'rank' => $rank++,
            ]);
        }
    }

    public static function overall(int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT lc.rank_position, lc.total_points, u.name, u.id AS user_id
            FROM leaderboard_cache lc
            JOIN users u ON u.id = lc.user_id
            JOIN tournament_members tm ON tm.user_id = u.id
                AND tm.tournament_id = lc.tournament_id
            WHERE lc.tournament_id = :tid
            ORDER BY lc.rank_position ASC
        ');

        $stmt->execute(['tid' => $tournamentId]);

        return $stmt->fetchAll();
    }

    public static function userRank(int $userId, int $tournamentId): ?int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT rank_position FROM leaderboard_cache
            WHERE user_id = :uid AND tournament_id = :tid
        ');

        $stmt->execute([
            'uid' => $userId,
            'tid' => $tournamentId,
        ]);

        $rank = $stmt->fetchColumn();

        return $rank !== false ? (int) $rank : null;
    }

    public static function userPoints(int $userId, int $tournamentId): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT total_points FROM leaderboard_cache
            WHERE user_id = :uid AND tournament_id = :tid
        ');

        $stmt->execute([
            'uid' => $userId,
            'tid' => $tournamentId,
        ]);

        $points = $stmt->fetchColumn();

        if ($points !== false) {
            return (int) $points;
        }

        $predictions = Prediction::forUser($userId, $tournamentId);
        $total = 0;

        foreach ($predictions as $prediction) {
            $total += (int) $prediction['points_awarded'];
        }

        return $total;
    }
}
