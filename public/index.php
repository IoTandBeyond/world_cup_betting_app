<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/app.php';
header('Location: ' . url('/'), true, 301);
exit;
