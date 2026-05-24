<?php

declare(strict_types=1);

namespace App\Controllers;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require BASE_PATH . '/app/views/' . $view . '.php';
    }

    protected function redirect(string $path): void
    {
        $location = str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            ? $path
            : url($path);

        header('Location: ' . $location);
        exit;
    }
}
