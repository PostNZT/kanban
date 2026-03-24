<?php

namespace App\Message;

final readonly class MoveCardMessage
{
    public function __construct(
        public int $cardId,
        public int $targetColumnId,
        public int $targetPosition,
    ) {
    }
}
