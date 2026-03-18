<?php

namespace App\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;

class BoardSerializer
{
    public function serializeBoard(Board $board): array
    {
        return [
            'id' => $board->getUuid(),
            'title' => $board->getTitle(),
            'columns' => array_map(
                fn(BoardColumn $column) => $this->serializeColumn($column),
                $board->getColumns()->toArray(),
            ),
            'createdAt' => $board->getCreatedAt()?->format('c'),
        ];
    }

    public function serializeBoardSummary(Board $board): array
    {
        return [
            'id' => $board->getUuid(),
            'title' => $board->getTitle(),
            'createdAt' => $board->getCreatedAt()?->format('c'),
        ];
    }

    public function serializeColumn(BoardColumn $column): array
    {
        return [
            'id' => $column->getId(),
            'title' => $column->getTitle(),
            'position' => $column->getPosition(),
            'cards' => array_map(
                fn(Card $card) => $this->serializeCard($card),
                $column->getCards()->toArray(),
            ),
        ];
    }

    public function serializeCard(Card $card): array
    {
        return [
            'id' => $card->getId(),
            'title' => $card->getTitle(),
            'description' => $card->getDescription(),
            'position' => $card->getPosition(),
            'createdAt' => $card->getCreatedAt()?->format('c'),
        ];
    }
}
