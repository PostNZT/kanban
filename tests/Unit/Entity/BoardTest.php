<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BoardTest extends TestCase
{
    public function testBoardCreatedWithTimestamp(): void
    {
        $board = new Board();
        $this->assertInstanceOf(\DateTimeImmutable::class, $board->getCreatedAt());
    }

    public function testSetAndGetTitle(): void
    {
        $board = new Board();
        $board->setTitle('My Board');
        $this->assertSame('My Board', $board->getTitle());
    }

    public function testSetAndGetOwner(): void
    {
        $board = new Board();
        $user = new User();
        $user->setEmail('owner@test.com');
        $board->setOwner($user);
        $this->assertSame($user, $board->getOwner());
    }

    public function testColumnsCollection(): void
    {
        $board = new Board();

        $col1 = new BoardColumn();
        $col1->setTitle('To Do');
        $col1->setPosition(0);

        $col2 = new BoardColumn();
        $col2->setTitle('Done');
        $col2->setPosition(1);

        $board->addColumn($col1);
        $board->addColumn($col2);

        $this->assertCount(2, $board->getColumns());
        $this->assertSame($board, $col1->getBoard());

        $board->removeColumn($col1);
        $this->assertCount(1, $board->getColumns());
    }

    public function testAddColumnDoesNotDuplicate(): void
    {
        $board = new Board();
        $col = new BoardColumn();
        $col->setTitle('Test');

        $board->addColumn($col);
        $board->addColumn($col);
        $this->assertCount(1, $board->getColumns());
    }
}
