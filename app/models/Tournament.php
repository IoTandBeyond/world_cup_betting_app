<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class Tournament
{
    public static function active(): ?array
    {
        $db = Database::connection();

        $stmt = $db->query("
            SELECT * FROM tournaments
            WHERE status IN ('active', 'upcoming')
            ORDER BY FIELD(status, 'active', 'upcoming'), start_date ASC
            LIMIT 1
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function all(): array
    {
        $db = Database::connection();

        return $db->query('
            SELECT t.*,
                   (SELECT COUNT(*) FROM teams WHERE tournament_id = t.id) AS team_count
            FROM tournaments t
            ORDER BY t.year DESC, t.name ASC
        ')->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('SELECT * FROM tournaments WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO tournaments (name, slug, year, start_date, end_date, status)
            VALUES (:name, :slug, :year, :start_date, :end_date, :status)
        ');

        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'year' => (int) $data['year'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'upcoming',
        ]);

        return (int) $db->lastInsertId();
    }

    public static function activate(int $id): void
    {
        $db = Database::connection();

        $db->prepare("
            UPDATE tournaments SET status = 'upcoming' WHERE id <> :id AND status = 'active'
        ")->execute(['id' => $id]);

        $db->prepare("
            UPDATE tournaments SET status = 'active' WHERE id = :id
        ")->execute(['id' => $id]);
    }

    public static function slugFromName(string $name, int $year): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug . '-' . $year;
    }
}
