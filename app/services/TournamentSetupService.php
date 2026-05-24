<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\Tournament;
use PDOException;

class TournamentSetupService
{
    public static function createTournament(array $input): int
    {
        $year = (int) $input['year'];
        $slug = trim($input['slug'] ?? '');

        if ($slug === '') {
            $slug = Tournament::slugFromName($input['name'], $year);
        }

        try {
            return self::createViaProcedure($input, $slug);
        } catch (PDOException) {
            return Tournament::create([
                'name' => $input['name'],
                'slug' => $slug,
                'year' => $year,
                'start_date' => $input['start_date'],
                'end_date' => $input['end_date'],
                'status' => $input['status'] ?? 'upcoming',
            ]);
        }
    }

    private static function createViaProcedure(array $input, string $slug): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            CALL sp_create_tournament(:name, :slug, :year, :start, :end, :status)
        ');

        $stmt->execute([
            'name' => $input['name'],
            'slug' => $slug,
            'year' => (int) $input['year'],
            'start' => $input['start_date'],
            'end' => $input['end_date'],
            'status' => $input['status'] ?? 'upcoming',
        ]);

        $row = $stmt->fetch();
        while ($stmt->nextRowset()) {
        }

        return (int) ($row['tournament_id'] ?? 0);
    }

    public static function activate(int $tournamentId): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare('CALL sp_activate_tournament(:id)');
            $stmt->execute(['id' => $tournamentId]);
            while ($stmt->nextRowset()) {
            }
        } catch (PDOException) {
            Tournament::activate($tournamentId);
        }
    }

    public static function importTeams(int $tournamentId, string $csv): array
    {
        try {
            $count = Team::callImportProcedure($tournamentId, $csv);

            return [
                'imported' => $count,
                'skipped' => 0,
                'errors' => [],
            ];
        } catch (PDOException) {
            return Team::importFromCsv($tournamentId, $csv);
        }
    }
}
