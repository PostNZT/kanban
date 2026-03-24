<?php

namespace App\Message;

final readonly class UpdateColumnMessage
{
    public function __construct(
        public int $columnId,
        public string $title,
    ) {
    }
}
