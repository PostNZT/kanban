<?php

namespace App\Message;

final readonly class DeleteColumnMessage
{
    public function __construct(
        public int $columnId,
    ) {
    }
}
