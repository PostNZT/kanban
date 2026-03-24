<?php

namespace App\Tests\Unit\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\CardRepository;
use App\Service\CardService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CardServiceTest extends TestCase
{
    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $user->setEmail("user{$id}@example.com");
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, $id);
        return $user;
    }

    private function makeBoard(User $owner, int $id = 1): Board
    {
        $board = new Board();
        $board->setTitle('Test Board');
        $board->setOwner($owner);
        $ref = new \ReflectionProperty(Board::class, 'id');
        $ref->setValue($board, $id);
        return $board;
    }

    private function makeColumn(Board $board, int $id = 1, int $position = 0): BoardColumn
    {
        $column = new BoardColumn();
        $column->setTitle("Column $id");
        $column->setPosition($position);
        $board->addColumn($column);
        $ref = new \ReflectionProperty(BoardColumn::class, 'id');
        $ref->setValue($column, $id);
        return $column;
    }

    private function makeCard(BoardColumn $column, int $id = 1, int $position = 0): Card
    {
        $card = new Card();
        $card->setTitle("Card $id");
        $card->setPosition($position);
        $column->addCard($card);
        $ref = new \ReflectionProperty(Card::class, 'id');
        $ref->setValue($card, $id);
        return $card;
    }

    public function testCreateCardSuccess(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn($column);
        $columnRepo->method('getNextPosition')->willReturn(0);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = new CardService(
            $entityManager,
            $this->createStub(CardRepository::class),
            $columnRepo,
        );

        $card = $service->createCard(10, 'New Card', 'Description', $user);

        $this->assertSame('New Card', $card->getTitle());
        $this->assertSame('Description', $card->getDescription());
        $this->assertSame(0, $card->getPosition());
    }

    public function testCreateCardColumnNotFound(): void
    {
        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn(null);

        $service = new CardService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CardRepository::class),
            $columnRepo,
        );

        $this->expectException(NotFoundHttpException::class);
        $service->createCard(999, 'Card', null, $this->makeUser());
    }

    public function testCreateCardAccessDenied(): void
    {
        $owner = $this->makeUser(1);
        $other = $this->makeUser(2);
        $board = $this->makeBoard($owner);
        $column = $this->makeColumn($board, 10);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn($column);

        $service = new CardService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CardRepository::class),
            $columnRepo,
        );

        $this->expectException(AccessDeniedHttpException::class);
        $service->createCard(10, 'Card', null, $other);
    }

    public function testVerifyCardOwnershipSuccess(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board);
        $card = $this->makeCard($column, 1);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($card);

        $service = new CardService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $this->createStub(BoardColumnRepository::class),
        );

        // Should not throw
        $service->verifyCardOwnership(1, $user);
        $this->assertTrue(true);
    }

    public function testVerifyCardOwnershipNotFound(): void
    {
        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn(null);

        $service = new CardService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $this->createStub(BoardColumnRepository::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $service->verifyCardOwnership(999, $this->makeUser());
    }

    public function testVerifyCardOwnershipAccessDenied(): void
    {
        $owner = $this->makeUser(1);
        $other = $this->makeUser(2);
        $board = $this->makeBoard($owner);
        $column = $this->makeColumn($board);
        $card = $this->makeCard($column, 1);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($card);

        $service = new CardService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $this->createStub(BoardColumnRepository::class),
        );

        $this->expectException(AccessDeniedHttpException::class);
        $service->verifyCardOwnership(1, $other);
    }
}
