<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class Team
{
    /**
     * Resolve a team by match CSV code (2-letter fifa_code or 3-letter short_name).
     */
    public static function findIdByFifaCode(
        int $tournamentId,
        string $code
    ): ?int {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT id FROM teams
            WHERE tournament_id = :tournament_id
              AND (fifa_code = :code OR short_name = :code)
            LIMIT 1
        ');

        $stmt->execute([
            'tournament_id' => $tournamentId,
            'code' => $code,
        ]);

        $id = $stmt->fetchColumn();

        return $id ? (int) $id : null;
    }

    public static function forTournament(int $tournamentId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM teams
            WHERE tournament_id = :tournament_id
            ORDER BY name ASC
        ');

        $stmt->execute(['tournament_id' => $tournamentId]);

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO teams (tournament_id, name, short_name, fifa_code, flag_url)
            VALUES (:tournament_id, :name, :short_name, :fifa_code, :flag_url)
        ');

        $stmt->execute([
            'tournament_id' => $data['tournament_id'],
            'name' => $data['name'],
            'short_name' => strtoupper($data['short_name']),
            'fifa_code' => strtoupper($data['fifa_code']),
            'flag_url' => $data['flag_url'] ?? null,
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public static function importFromCsv(int $tournamentId, string $csv): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $lines = preg_split('/\r\n|\r|\n/', $csv) ?: [];

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^name\s*,/i', $line)) {
                continue;
            }

            $parts = array_map('trim', str_getcsv($line, ',', '"', '\\'));

            if (count($parts) < 3) {
                $errors[] = 'Line ' . ($lineNum + 1) . ': need name, short_name, fifa_code';
                continue;
            }

            [$name, $short, $fifa] = $parts;

            try {
                self::create([
                    'tournament_id' => $tournamentId,
                    'name' => $name,
                    'short_name' => $short,
                    'fifa_code' => $fifa,
                ]);
                $imported++;
            } catch (\PDOException $e) {
                if ((int) $e->getCode() === 23000) {
                    $skipped++;
                } else {
                    $errors[] = 'Line ' . ($lineNum + 1) . ': ' . $name;
                }
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    public static function callAddProcedure(
        int $tournamentId,
        string $name,
        string $shortName,
        string $fifaCode,
        ?string $flagUrl = null
    ): void {
        $db = Database::connection();

        $stmt = $db->prepare('
            CALL sp_add_team(:tid, :name, :short, :fifa, :flag)
        ');

        $stmt->execute([
            'tid' => $tournamentId,
            'name' => $name,
            'short' => $shortName,
            'fifa' => $fifaCode,
            'flag' => $flagUrl,
        ]);

        while ($stmt->nextRowset()) {
            // consume result sets from procedure
        }
    }

    public static function callImportProcedure(
        int $tournamentId,
        string $csv
    ): int {
        $db = Database::connection();

        $stmt = $db->prepare('CALL sp_import_teams(:tid, :csv)');
        $stmt->execute([
            'tid' => $tournamentId,
            'csv' => $csv,
        ]);

        $row = $stmt->fetch();

        while ($stmt->nextRowset()) {
            // consume
        }

        return (int) ($row['teams_imported'] ?? 0);
    }
}
