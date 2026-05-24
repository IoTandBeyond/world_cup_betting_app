<?php

declare(strict_types=1);

use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/helpers.php';

$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', app_web_root());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
