<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class BonusPrediction
{
    public static function find(int $userId, int $tournamentId): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM bonus_predictions
            WHERE user_id = :user_id AND tournament_id = :tournament_id
        ');

        $stmt->execute([
            'user_id' => $userId,
            'tournament_id' => $tournamentId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function upsert(int $userId, int $tournamentId, array $data): void
    {
        $db = Database::connection();
        $existing = self::find($userId, $tournamentId);

        if ($existing) {
            $stmt = $db->prepare('
                UPDATE bonus_predictions SET
                    world_cup_winner_team_id = :winner,
                    top_scorer_player_id = :scorer,
                    best_goalkeeper_player_id = :keeper,
                    mvp_player_id = :mvp
                WHERE id = :id
            ');

            $stmt->execute([
                'winner' => $data['world_cup_winner_team_id'] ?: null,
                'scorer' => $data['top_scorer_player_id'] ?: null,
                'keeper' => $data['best_goalkeeper_player_id'] ?: null,
                'mvp' => $data['mvp_player_id'] ?: null,
                'id' => $existing['id'],
            ]);

            return;
        }

        $stmt = $db->prepare('
            INSERT INTO bonus_predictions (
                user_id, tournament_id,
                world_cup_winner_team_id,
                top_scorer_player_id,
                best_goalkeeper_player_id,
                mvp_player_id
            ) VALUES (
                :user_id, :tournament_id,
                :winner, :scorer, :keeper, :mvp
            )
        ');

        $stmt->execute([
            'user_id' => $userId,
            'tournament_id' => $tournamentId,
            'winner' => $data['world_cup_winner_team_id'] ?: null,
            'scorer' => $data['top_scorer_player_id'] ?: null,
            'keeper' => $data['best_goalkeeper_player_id'] ?: null,
            'mvp' => $data['mvp_player_id'] ?: null,
        ]);
    }
}
