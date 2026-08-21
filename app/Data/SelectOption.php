<?php

namespace App\Data;

final readonly class SelectOption
{
    public function __construct(
        public int $value,
        public string $label,
    ) {}
}
