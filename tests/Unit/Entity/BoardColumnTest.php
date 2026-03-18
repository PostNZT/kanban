<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;
use PHPUnit\Framework\TestCase;

class BoardColumnTest extends TestCase
{
    public function testSetAndGetTitle(): void
    {
        $col = new BoardColumn();
        $col->setTitle('In Progress');
        $this->assertSame('In Progress', $col->getTitle());
    }

    public function testSetAndGetPosition(): void
    {
        $col = new BoardColumn();
        $col->setPosition(2);
        $this->assertSame(2, $col->getPosition());
    }

    public function testSetAndGetBoard(): void
    {
        $col = new BoardColumn();
        $board = new Board();
        $board->setTitle('Test');
        $col->setBoard($board);
        $this->assertSame($board, $col->getBoard());
    }

    public function testCardsCollection(): void
    {
        $col = new BoardColumn();
        $col->setTitle('To Do');

        $card1 = new Card();
        $card1->setTitle('Task 1');
        $card2 = new Card();
        $card2->setTitle('Task 2');

        $col->addCard($card1);
        $col->addCard($card2);

        $this->assertCount(2, $col->getCards());
        $this->assertSame($col, $card1->getBoardColumn());

        $col->removeCard($card1);
        $this->assertCount(1, $col->getCards());
    }
}
