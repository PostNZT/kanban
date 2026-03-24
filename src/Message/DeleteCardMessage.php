<?php

namespace App\Message;

final readonly class DeleteCardMessage
{
    public function __construct(
        public int $cardId,
    ) {
    }
}
