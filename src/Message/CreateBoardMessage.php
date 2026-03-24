<?php

namespace App\Message;

final readonly class CreateBoardMessage
{
    public function __construct(
        public string $boardUuid,
        public int $ownerId,
        public string $title,
    ) {
    }
}
