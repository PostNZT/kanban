<?php

namespace App\Message;

final readonly class UpdateBoardMessage
{
    public function __construct(
        public string $boardUuid,
        public string $title,
    ) {
    }
}
