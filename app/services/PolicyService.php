<?php

declare(strict_types=1);

namespace App\Services;

class PolicyService
{
    public const VERSION = '1.0';

    public static function currentVersion(): string
    {
        return self::VERSION;
    }
}
