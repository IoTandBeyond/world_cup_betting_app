<?php

declare(strict_types=1);

/**
 * Loads App\* classes from lowercase app/ folders (controllers, services, models, …).
 * Required on Linux when Composer's classmap is stale; safe to keep after dump-autoload.
 */
spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }

    $segments = explode('\\', substr($class, 4));
    if ($segments === []) {
        return;
    }

    $className = array_pop($segments);
    $dir = BASE_PATH . '/app';

    foreach ($segments as $segment) {
        $dir .= '/' . strtolower($segment);
    }

    $file = $dir . '/' . $className . '.php';

    if (is_file($file)) {
        require $file;
    }
});
