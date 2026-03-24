<?php

namespace App\Message;

final readonly class DeleteBoardMessage
{
    public function __construct(
        public string $boardUuid,
    ) {
    }
}
