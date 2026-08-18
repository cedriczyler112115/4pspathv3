<?php

namespace App\Support;

final class Semester
{
    public static function label(int|string|null $value): string
    {
        return match ((string) $value) {
            '1' => '1st Semester',
            '2' => '2nd Semester',
            '3' => 'Both Semester',
            default => (string) ($value ?? '-'),
        };
    }
}
