<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $db = Database::connection();
        $email = strtolower(trim($email));

        $stmt = $db->prepare('
            SELECT * FROM users
            WHERE email = :email
            LIMIT 1
        ');

        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            SELECT * FROM users
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            INSERT INTO users (
                uuid, name, email, password_hash, role, must_change_password
            ) VALUES (
                :uuid, :name, :email, :password_hash, :role, :must_change_password
            )
        ');

        $stmt->execute([
            'uuid' => self::uuid(),
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password_hash' => password_hash(
                $data['password'],
                PASSWORD_BCRYPT
            ),
            'role' => $data['role'] ?? 'user',
            'must_change_password' => !empty($data['must_change_password']) ? 1 : 0,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function updatePassword(int $id, string $password): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE users
            SET password_hash = :password_hash,
                must_change_password = 0,
                updated_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public static function setTemporaryPassword(int $id, string $password): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE users
            SET password_hash = :password_hash,
                must_change_password = 1,
                updated_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public static function mustChangePassword(int $id): bool
    {
        $user = self::findById($id);

        return $user && (int) $user['must_change_password'] === 1;
    }

    public static function hasAcceptedPolicy(?array $user): bool
    {
        if (!$user || empty($user['policy_accepted_at'])) {
            return false;
        }

        return ($user['policy_version'] ?? '') === \App\Services\PolicyService::currentVersion();
    }

    public static function recordPolicyAcceptance(int $id, string $version): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE users
            SET policy_accepted_at = NOW(),
                policy_version = :version,
                updated_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'version' => $version,
        ]);
    }

    public static function updateLastLogin(int $id): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE users SET last_login_at = NOW() WHERE id = :id
        ');

        $stmt->execute(['id' => $id]);
    }

    public static function all(): array
    {
        $db = Database::connection();

        return $db->query('
            SELECT id, name, email, role, is_active, must_change_password,
                   policy_accepted_at, policy_version,
                   last_login_at, created_at
            FROM users
            ORDER BY name ASC
        ')->fetchAll();
    }

    /** All users with member tournaments and hosted tournament (for admin list). */
    public static function allWithTournaments(): array
    {
        $db = Database::connection();

        $rows = $db->query('
            SELECT u.id, u.name, u.email, u.role, u.is_active,
                   u.must_change_password, u.policy_accepted_at, u.policy_version,
                   u.last_login_at, u.created_at,
                   GROUP_CONCAT(
                       DISTINCT CONCAT(t.name, " (", t.year, ")")
                       ORDER BY t.name SEPARATOR ", "
                   ) AS member_tournaments,
                   ht.name AS hosted_tournament_name,
                   ht.year AS hosted_tournament_year
            FROM users u
            LEFT JOIN tournament_members tm ON tm.user_id = u.id
            LEFT JOIN tournaments t ON t.id = tm.tournament_id
            LEFT JOIN tournaments ht ON ht.host_user_id = u.id
            GROUP BY u.id, u.name, u.email, u.role, u.is_active,
                     u.must_change_password, u.policy_accepted_at, u.policy_version,
                     u.last_login_at, u.created_at,
                     ht.name, ht.year
            ORDER BY u.name ASC
        ')->fetchAll();

        foreach ($rows as &$row) {
            $row['tournament_label'] = self::formatTournamentLabel($row);
        }
        unset($row);

        return $rows;
    }

    /** @param array<string, mixed> $user */
    public static function formatTournamentLabel(array $user): string
    {
        if (($user['role'] ?? '') === 'host' && !empty($user['hosted_tournament_name'])) {
            $year = !empty($user['hosted_tournament_year'])
                ? ' (' . (int) $user['hosted_tournament_year'] . ')'
                : '';

            return $user['hosted_tournament_name'] . $year . ' (host)';
        }

        if (!empty($user['member_tournaments'])) {
            return $user['member_tournaments'];
        }

        if (($user['role'] ?? '') === 'admin') {
            return '—';
        }

        return '—';
    }

    public static function setActive(int $id, bool $active): void
    {
        $db = Database::connection();

        $stmt = $db->prepare('
            UPDATE users SET is_active = :active WHERE id = :id
        ');

        $stmt->execute([
            'id' => $id,
            'active' => $active ? 1 : 0,
        ]);
    }

    public static function count(): int
    {
        $db = Database::connection();

        return (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }
}
