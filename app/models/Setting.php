<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class Setting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $db = Database::connection();
        $rows = $db->query('SELECT `key`, `value` FROM settings')->fetchAll();
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        self::$cache = $settings;

        return $settings;
    }

    public static function get(string $key, int $default = 0): int
    {
        $settings = self::all();

        return (int) ($settings[$key] ?? $default);
    }
}
