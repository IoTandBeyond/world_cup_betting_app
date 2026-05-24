<?php

declare(strict_types=1);

namespace App\Helpers;

class Validator
{
    public static function required(?string $value): bool
    {
        return trim($value ?? '') !== '';
    }

    public static function email(?string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function min(?string $value, int $length): bool
    {
        return strlen($value ?? '') >= $length;
    }
}
