<?php

namespace App\Message;

final readonly class ReorderColumnsMessage
{
    public function __construct(
        public string $boardUuid,
        public array $orderedColumnIds,
    ) {
    }
}
