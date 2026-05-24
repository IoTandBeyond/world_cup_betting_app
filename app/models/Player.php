<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class Player
{
    public static function forTournament(int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT p.*, t.name AS team_name, t.short_name AS team_short, t.fifa_code
            FROM players p
            JOIN teams t ON t.id = p.team_id
            WHERE t.tournament_id = :tournament_id
            ORDER BY t.name ASC, p.name ASC
        ');

        $stmt->execute(['tournament_id' => $tournamentId]);

        return $stmt->fetchAll();
    }

    public static function forTournamentByPosition(
        int $tournamentId,
        string $position
    ): array {
        $all = self::forTournament($tournamentId);

        return array_values(array_filter(
            $all,
            static fn ($p) => $p['position'] === $position
        ));
    }

    public static function findOrCreate(
        int $teamId,
        string $name,
        string $position
    ): int {
        $db = Database::connection();
        $name = trim($name);

        $stmt = $db->prepare('
            SELECT id FROM players
            WHERE team_id = :team_id AND name = :name
            LIMIT 1
        ');

        $stmt->execute([
            'team_id' => $teamId,
            'name' => $name,
        ]);

        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        $insert = $db->prepare('
            INSERT INTO players (team_id, name, position)
            VALUES (:team_id, :name, :position)
        ');

        $insert->execute([
            'team_id' => $teamId,
            'name' => $name,
            'position' => $position,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT p.*, t.name AS team_name, t.fifa_code
            FROM players p
            JOIN teams t ON t.id = p.team_id
            WHERE p.id = :id
        ');

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
