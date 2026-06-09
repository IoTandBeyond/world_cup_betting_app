<?php

declare(strict_types=1);

use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/bootstrap/autoload_app.php';
require_once BASE_PATH . '/app/helpers.php';

$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

$appTimezone = $_ENV['APP_TIMEZONE'] ?? 'UTC';

if (!in_array($appTimezone, timezone_identifiers_list(), true)) {
    $appTimezone = 'UTC';
}

date_default_timezone_set($appTimezone);

if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', app_web_root());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
