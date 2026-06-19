<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class Prediction
{
    public static function find(int $userId, int $matchId): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM predictions
            WHERE user_id = :user_id AND match_id = :match_id
        ');

        $stmt->execute([
            'user_id' => $userId,
            'match_id' => $matchId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function forUser(int $userId, int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT p.*, m.kickoff_at
            FROM predictions p
            JOIN matches m ON m.id = p.match_id
            WHERE p.user_id = :user_id
              AND m.tournament_id = :tournament_id
        ');

        $stmt->execute([
            'user_id' => $userId,
            'tournament_id' => $tournamentId,
        ]);

        $rows = $stmt->fetchAll();
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(int) $row['match_id']] = $row;
        }

        return $indexed;
    }

    public static function upsert(
        int $userId,
        int $matchId,
        int $homeScore,
        int $awayScore
    ): void {
        $db = Database::connection();
        $existing = self::find($userId, $matchId);

        if ($existing) {
            $stmt = $db->prepare('
                UPDATE predictions
                SET predicted_home_score = :home,
                    predicted_away_score = :away
                WHERE id = :id
            ');

            $stmt->execute([
                'home' => $homeScore,
                'away' => $awayScore,
                'id' => $existing['id'],
            ]);

            return;
        }

        $stmt = $db->prepare('
            INSERT INTO predictions (
                user_id, match_id,
                predicted_home_score, predicted_away_score
            ) VALUES (
                :user_id, :match_id, :home, :away
            )
        ');

        $stmt->execute([
            'user_id' => $userId,
            'match_id' => $matchId,
            'home' => $homeScore,
            'away' => $awayScore,
        ]);
    }

    public static function unscoredForMatch(int $matchId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM predictions
            WHERE match_id = :match_id AND scored_at IS NULL
        ');

        $stmt->execute(['match_id' => $matchId]);

        return $stmt->fetchAll();
    }

    public static function forMatch(int $matchId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM predictions
            WHERE match_id = :match_id
        ');

        $stmt->execute(['match_id' => $matchId]);

        return $stmt->fetchAll();
    }

    public static function markScored(
        int $id,
        int $points
    ): void {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE predictions
            SET points_awarded = :points, scored_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'points' => $points,
        ]);
    }
}
