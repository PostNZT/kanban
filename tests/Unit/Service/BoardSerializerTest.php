<?php

namespace App\Tests\Unit\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;
use App\Service\BoardSerializer;
use PHPUnit\Framework\TestCase;

class BoardSerializerTest extends TestCase
{
    private BoardSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new BoardSerializer();
    }

    public function testSerializeBoardReturnsCorrectStructure(): void
    {
        $board = new Board();
        $board->setTitle('Sprint Board');
        $board->setCreatedAt(new \DateTimeImmutable('2025-01-15T10:00:00+00:00'));

        $column = new BoardColumn();
        $column->setTitle('To Do');
        $column->setPosition(0);
        $ref = new \ReflectionProperty(BoardColumn::class, 'id');
        $ref->setValue($column, 1);

        $board->addColumn($column);

        $result = $this->serializer->serializeBoard($board);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('createdAt', $result);

        $this->assertSame('Sprint Board', $result['title']);
        $this->assertSame($board->getUuid(), $result['id']);
        $this->assertSame('2025-01-15T10:00:00+00:00', $result['createdAt']);
        $this->assertCount(1, $result['columns']);
        $this->assertSame('To Do', $result['columns'][0]['title']);
    }

    public function testSerializeBoardSummaryReturnsCorrectStructure(): void
    {
        $board = new Board();
        $board->setTitle('Summary Board');
        $board->setCreatedAt(new \DateTimeImmutable('2025-06-01T12:00:00+00:00'));

        $column = new BoardColumn();
        $column->setTitle('Backlog');
        $column->setPosition(0);
        $board->addColumn($column);

        $result = $this->serializer->serializeBoardSummary($board);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('createdAt', $result);
        $this->assertArrayNotHasKey('columns', $result);

        $this->assertSame('Summary Board', $result['title']);
        $this->assertSame($board->getUuid(), $result['id']);
        $this->assertSame('2025-06-01T12:00:00+00:00', $result['createdAt']);
    }

    public function testSerializeColumnReturnsCorrectStructure(): void
    {
        $column = new BoardColumn();
        $column->setTitle('In Progress');
        $column->setPosition(2);

        $ref = new \ReflectionProperty(BoardColumn::class, 'id');
        $ref->setValue($column, 42);

        $card = new Card();
        $card->setTitle('Fix bug');
        $card->setDescription('Critical bug');
        $card->setPosition(0);
        $cardRef = new \ReflectionProperty(Card::class, 'id');
        $cardRef->setValue($card, 10);

        $column->addCard($card);

        $result = $this->serializer->serializeColumn($column);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertArrayHasKey('cards', $result);

        $this->assertSame(42, $result['id']);
        $this->assertSame('In Progress', $result['title']);
        $this->assertSame(2, $result['position']);
        $this->assertCount(1, $result['cards']);
    }

    public function testSerializeCardReturnsCorrectStructure(): void
    {
        $card = new Card();
        $card->setTitle('Implement feature');
        $card->setDescription('Add login page');
        $card->setPosition(3);
        $card->setCreatedAt(new \DateTimeImmutable('2025-03-20T08:30:00+00:00'));

        $ref = new \ReflectionProperty(Card::class, 'id');
        $ref->setValue($card, 99);

        $result = $this->serializer->serializeCard($card);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertArrayHasKey('createdAt', $result);

        $this->assertSame(99, $result['id']);
        $this->assertSame('Implement feature', $result['title']);
        $this->assertSame('Add login page', $result['description']);
        $this->assertSame(3, $result['position']);
        $this->assertSame('2025-03-20T08:30:00+00:00', $result['createdAt']);
    }

    public function testSerializeBoardUsesUuidNotId(): void
    {
        $board = new Board();
        $board->setTitle('UUID Board');

        $ref = new \ReflectionProperty(Board::class, 'id');
        $ref->setValue($board, 1);

        $result = $this->serializer->serializeBoard($board);

        $this->assertNotSame(1, $result['id']);
        $this->assertSame($board->getUuid(), $result['id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $result['id'],
        );
    }

    public function testSerializeCardWithNullDescription(): void
    {
        $card = new Card();
        $card->setTitle('No description card');
        $card->setPosition(0);

        $ref = new \ReflectionProperty(Card::class, 'id');
        $ref->setValue($card, 5);

        $result = $this->serializer->serializeCard($card);

        $this->assertArrayHasKey('description', $result);
        $this->assertNull($result['description']);
    }
}
