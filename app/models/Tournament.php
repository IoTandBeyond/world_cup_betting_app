<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class Tournament
{
    public static function active(): ?array
    {
        $list = self::activeList();

        return $list[0] ?? null;
    }

    /** @return list<array> */
    public static function activeList(): array
    {
        $db = Database::connection();

        return $db->query("
            SELECT * FROM tournaments
            WHERE status IN ('active', 'upcoming')
            ORDER BY FIELD(status, 'active', 'upcoming'), start_date ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function all(): array
    {
        $db = Database::connection();

        return $db->query('
            SELECT t.*,
                   (SELECT COUNT(*) FROM teams WHERE tournament_id = t.id) AS team_count,
                   h.name AS host_name,
                   h.email AS host_email
            FROM tournaments t
            LEFT JOIN users h ON h.id = t.host_user_id
            ORDER BY t.year DESC, t.name ASC
        ')->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT t.*, h.name AS host_name, h.email AS host_email
            FROM tournaments t
            LEFT JOIN users h ON h.id = t.host_user_id
            WHERE t.id = :id
        ');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findByHostUserId(int $userId): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM tournaments
            WHERE host_user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO tournaments (
                name, slug, year, start_date, end_date, status, host_user_id
            ) VALUES (
                :name, :slug, :year, :start_date, :end_date, :status, :host_user_id
            )
        ');

        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'year' => (int) $data['year'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'upcoming',
            'host_user_id' => $data['host_user_id'] ?? null,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function setHost(int $tournamentId, int $hostUserId): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE tournaments
            SET host_user_id = :host_user_id
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $tournamentId,
            'host_user_id' => $hostUserId,
        ]);
    }

    /** Activate without deactivating other tournaments (parallel pools). */
    public static function activate(int $id): void
    {
        $db = Database::connection();

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
