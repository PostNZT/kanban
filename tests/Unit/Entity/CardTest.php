<?php

namespace App\Tests\Unit\Entity;

use App\Entity\BoardColumn;
use App\Entity\Card;
use PHPUnit\Framework\TestCase;

class CardTest extends TestCase
{
    public function testCardCreatedWithTimestamp(): void
    {
        $card = new Card();
        $this->assertInstanceOf(\DateTimeImmutable::class, $card->getCreatedAt());
    }

    public function testSetAndGetTitle(): void
    {
        $card = new Card();
        $card->setTitle('My Task');
        $this->assertSame('My Task', $card->getTitle());
    }

    public function testSetAndGetDescription(): void
    {
        $card = new Card();
        $card->setDescription('Some details');
        $this->assertSame('Some details', $card->getDescription());

        $card->setDescription(null);
        $this->assertNull($card->getDescription());
    }

    public function testSetAndGetPosition(): void
    {
        $card = new Card();
        $card->setPosition(3);
        $this->assertSame(3, $card->getPosition());
    }

    public function testSetAndGetBoardColumn(): void
    {
        $card = new Card();
        $col = new BoardColumn();
        $col->setTitle('To Do');

        $card->setBoardColumn($col);
        $this->assertSame($col, $card->getBoardColumn());
    }
}
