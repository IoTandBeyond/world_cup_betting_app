<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_web_root')) {
    /**
     * Web path prefix when the app is not at the domain root
     * (e.g. "/worldcuppoll" for http://localhost/worldcuppoll/).
     */
    function app_web_root(): string
    {
        if (!empty($_ENV['APP_BASE_PATH'])) {
            return '/' . trim($_ENV['APP_BASE_PATH'], '/');
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));

        if ($dir === '/' || $dir === '.' || $dir === '') {
            return '';
        }

        return $dir;
    }
}

if (!function_exists('request_path')) {
    function request_path(?string $requestUri = null): string
    {
        $path = parse_url(
            $requestUri ?? $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        ) ?: '/';

        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        $root = defined('WEB_ROOT') ? WEB_ROOT : app_web_root();

        if ($root !== '' && str_starts_with($path, $root)) {
            $path = substr($path, strlen($root)) ?: '/';
        }

        return rtrim($path, '/') ?: '/';
    }
}

if (!function_exists('url')) {
    /** Build a path relative to the app web root (for links and redirects). */
    function url(string $path = '/'): string
    {
        $root = defined('WEB_ROOT') ? WEB_ROOT : app_web_root();

        if ($path === '' || $path === '/') {
            return $root === '' ? '/' : $root . '/';
        }

        return $root . $path;
    }
}

if (!function_exists('parse_csv_content')) {
    /**
     * @return list<list<string>>
     */
    function parse_csv_content(string $csv): array
    {
        $rows = [];
        $stream = fopen('php://memory', 'r+');

        if ($stream === false) {
            return [];
        }

        fwrite($stream, $csv);
        rewind($stream);

        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            $rows[] = array_map(
                static fn ($cell) => trim((string) $cell),
                $row
            );
        }

        fclose($stream);

        return $rows;
    }
}

if (!function_exists('fifa_flag_iso')) {
    /**
     * ISO code for flagcdn.com. Teams store 2-letter codes in fifa_code (e.g. AR, BR).
     * Legacy 3-letter FIFA codes are mapped when passed directly.
     */
    function fifa_flag_iso(string $fifaCode): string
    {
        $code = strtoupper(trim($fifaCode));

        if (strlen($code) === 2) {
            return strtolower($code);
        }

        $map = [
            'ENG' => 'gb-eng',
            'SCO' => 'gb-sct',
            'WAL' => 'gb-wls',
            'NIR' => 'gb-nir',
            'USA' => 'us',
            'KOR' => 'kr',
            'RSA' => 'za',
            'KSA' => 'sa',
            'UAE' => 'ae',
            'CIV' => 'ci',
            'CPV' => 'cv',
            'CUR' => 'cw',
            'GER' => 'de',
            'NED' => 'nl',
        ];

        return $map[$code] ?? strtolower($code);
    }
}

if (!function_exists('team_flag_url')) {
    function team_flag_url(string $fifaCode, ?string $customUrl = null): string
    {
        if ($customUrl !== null && $customUrl !== '') {
            return $customUrl;
        }

        $iso = fifa_flag_iso($fifaCode);

        return 'https://flagcdn.com/h24/' . $iso . '.png';
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('match_stage_label')) {
    function match_stage_label(string $stage): string
    {
        return ucwords(str_replace('_', ' ', $stage));
    }
}

if (!function_exists('app_timezone')) {
    /** IANA timezone for match kickoffs and display (e.g. America/Toronto). */
    function app_timezone(): string
    {
        return date_default_timezone_get();
    }
}

if (!function_exists('format_kickoff')) {
    /** Format a stored kickoff datetime for display in APP_TIMEZONE. */
    function format_kickoff(?string $kickoffAt, string $format = 'M j, H:i'): string
    {
        if ($kickoffAt === null || $kickoffAt === '') {
            return '—';
        }

        $dt = date_create($kickoffAt, timezone_open(app_timezone()));

        if ($dt === false) {
            return $kickoffAt;
        }

        return $dt->format($format);
    }
}

if (!function_exists('absolute_url')) {
    /** Full URL (for invitation emails). Uses APP_URL when set. */
    function absolute_url(string $path = '/'): string
    {
        $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');

        if ($appUrl !== '') {
            return $appUrl . url($path);
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . url($path);
    }
}
