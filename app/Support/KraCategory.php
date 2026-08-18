<?php

namespace App\Support;

final class KraCategory
{
    public static function label(int|string|null $value): string
    {
        return match ((string) $value) {
            '1' => 'Strategic Function',
            '2' => 'Core Function',
            '3' => 'Support Function',
            default => (string) ($value ?? '-'),
        };
    }
}
