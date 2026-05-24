<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Prediction;
use App\Models\Setting;

class ScoringService
{
    public static function scoreMatch(int $matchId): int
    {
        $match = MatchModel::findById($matchId);

        if (
            !$match
            || $match['status'] !== 'finished'
            || $match['home_score'] === null
            || $match['away_score'] === null
        ) {
            return 0;
        }

        $home = (int) $match['home_score'];
        $away = (int) $match['away_score'];
        $scored = 0;

        foreach (Prediction::unscoredForMatch($matchId) as $prediction) {
            $points = self::calculatePoints(
                $home,
                $away,
                (int) $prediction['predicted_home_score'],
                (int) $prediction['predicted_away_score']
            );

            Prediction::markScored((int) $prediction['id'], $points);

            if ($points > 0) {
                self::logPoints(
                    (int) $prediction['user_id'],
                    (int) $prediction['id'],
                    $points,
                    "Match #{$matchId} prediction"
                );
            }

            $scored++;
        }

        if ($scored > 0) {
            LeaderboardService::rebuild((int) $match['tournament_id']);
        }

        return $scored;
    }

    public static function calculatePoints(
        int $home,
        int $away,
        int $predHome,
        int $predAway
    ): int {
        if ($home === $predHome && $away === $predAway) {
            return Setting::get('points_exact_score', 5);
        }

        $actualDiff = $home <=> $away;
        $predDiff = $predHome <=> $predAway;

        if ($actualDiff === $predDiff) {
            if ($actualDiff === 0) {
                return Setting::get('points_correct_draw', 3);
            }

            return Setting::get('points_correct_winner', 3);
        }

        return 0;
    }

    private static function logPoints(
        int $userId,
        int $predictionId,
        int $points,
        string $reason
    ): void {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO points_log (user_id, prediction_id, points, reason)
            VALUES (:user_id, :prediction_id, :points, :reason)
        ');

        $stmt->execute([
            'user_id' => $userId,
            'prediction_id' => $predictionId,
            'points' => $points,
            'reason' => $reason,
        ]);
    }
}
