<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap/app.php';

/** @var \App\Services\Router $router */
$router = require __DIR__ . '/routes/web.php';

$router->dispatch(
    request_path(),
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);
