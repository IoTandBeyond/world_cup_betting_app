<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class MatchModel
{
    public static function count(): int
    {
        $db = Database::connection();

        return (int) $db->query('SELECT COUNT(*) FROM matches')->fetchColumn();
    }

    public static function pendingCount(): int
    {
        $db = Database::connection();

        return (int) $db->query("
            SELECT COUNT(*) FROM matches WHERE status = 'scheduled'
        ")->fetchColumn();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT m.*,
                   ht.name AS home_team_name,
                   ht.short_name AS home_short_name,
                   ht.fifa_code AS home_fifa_code,
                   ht.flag_url AS home_flag_url,
                   at.name AS away_team_name,
                   at.short_name AS away_short_name,
                   at.fifa_code AS away_fifa_code,
                   at.flag_url AS away_flag_url
            FROM matches m
            JOIN teams ht ON ht.id = m.home_team_id
            JOIN teams at ON at.id = m.away_team_id
            WHERE m.id = :id
        ');

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function forTournament(int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT m.*,
                   ht.name AS home_team_name,
                   ht.short_name AS home_short_name,
                   ht.fifa_code AS home_fifa_code,
                   ht.flag_url AS home_flag_url,
                   at.name AS away_team_name,
                   at.short_name AS away_short_name,
                   at.fifa_code AS away_fifa_code,
                   at.flag_url AS away_flag_url
            FROM matches m
            JOIN teams ht ON ht.id = m.home_team_id
            JOIN teams at ON at.id = m.away_team_id
            WHERE m.tournament_id = :tournament_id
            ORDER BY m.kickoff_at ASC
        ');

        $stmt->execute(['tournament_id' => $tournamentId]);

        return $stmt->fetchAll();
    }

    public static function firstKickoffAt(int $tournamentId): ?string
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT MIN(kickoff_at) FROM matches
            WHERE tournament_id = :tournament_id
        ');

        $stmt->execute(['tournament_id' => $tournamentId]);

        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function canPredict(?array $match): bool
    {
        if (!$match || !(int) $match['allow_predictions']) {
            return false;
        }

        if ($match['status'] !== 'scheduled') {
            return false;
        }

        $kickoff = date_create(
            $match['kickoff_at'],
            timezone_open(date_default_timezone_get())
        );

        if ($kickoff === false) {
            return false;
        }

        return $kickoff->getTimestamp() > time();
    }

    public static function updateScore(
        int $id,
        int $homeScore,
        int $awayScore,
        string $status = 'finished'
    ): void {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE matches
            SET home_score = :home_score,
                away_score = :away_score,
                status = :status,
                allow_predictions = 0
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status' => $status,
        ]);
    }

    public static function setAllowPredictions(
        int $id,
        bool $allowed
    ): void {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE matches SET allow_predictions = :allowed WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'allowed' => $allowed ? 1 : 0,
        ]);
    }

    public static function exists(
        int $tournamentId,
        int $homeTeamId,
        int $awayTeamId,
        string $kickoffAt
    ): bool {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT 1 FROM matches
            WHERE tournament_id = :tournament_id
              AND home_team_id = :home_team_id
              AND away_team_id = :away_team_id
              AND kickoff_at = :kickoff_at
            LIMIT 1
        ');

        $stmt->execute([
            'tournament_id' => $tournamentId,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'kickoff_at' => $kickoffAt,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO matches (
                tournament_id, stage, group_name,
                home_team_id, away_team_id, kickoff_at, venue, status
            ) VALUES (
                :tournament_id, :stage, :group_name,
                :home_team_id, :away_team_id, :kickoff_at, :venue, :status
            )
        ');

        $stmt->execute([
            'tournament_id' => $data['tournament_id'],
            'stage' => $data['stage'],
            'group_name' => $data['group_name'] ?? null,
            'home_team_id' => $data['home_team_id'],
            'away_team_id' => $data['away_team_id'],
            'kickoff_at' => $data['kickoff_at'],
            'venue' => $data['venue'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
        ]);

        return (int) $db->lastInsertId();
    }
}
