<?php

namespace App\Message;

final readonly class UpdateCardMessage
{
    public function __construct(
        public int $cardId,
        public ?string $title,
        public ?string $description,
        public bool $descriptionProvided,
    ) {
    }
}
