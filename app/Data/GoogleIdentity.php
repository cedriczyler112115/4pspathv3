<?php

namespace App\Data;

final readonly class GoogleIdentity
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
    ) {}
}
