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

    public static function mustChangePassword(int $id): bool
    {
        $user = self::findById($id);

        return $user && (int) $user['must_change_password'] === 1;
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
                   last_login_at, created_at
            FROM users
            ORDER BY name ASC
        ')->fetchAll();
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
