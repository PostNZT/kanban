<?php

namespace App\Tests\Unit\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\BoardRepository;
use App\Repository\CardRepository;
use App\Service\CardReorderService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CardReorderServiceTest extends TestCase
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

    private function makeColumn(Board $board, int $id, int $position = 0): BoardColumn
    {
        $column = new BoardColumn();
        $column->setTitle("Column $id");
        $column->setPosition($position);
        $board->addColumn($column);
        $ref = new \ReflectionProperty(BoardColumn::class, 'id');
        $ref->setValue($column, $id);
        return $column;
    }

    private function makeCard(BoardColumn $column, int $id, int $position = 0): Card
    {
        $card = new Card();
        $card->setTitle("Card $id");
        $card->setPosition($position);
        $column->addCard($card);
        $ref = new \ReflectionProperty(Card::class, 'id');
        $ref->setValue($card, $id);
        return $card;
    }

    public function testMoveCardNotFound(): void
    {
        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn(null);

        $service = new CardReorderService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $this->createStub(BoardColumnRepository::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $service->moveCard(999, 1, 0, $this->makeUser());
    }

    public function testMoveCardAccessDenied(): void
    {
        $owner = $this->makeUser(1);
        $other = $this->makeUser(2);
        $board = $this->makeBoard($owner);
        $column = $this->makeColumn($board, 10);
        $card = $this->makeCard($column, 1);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($card);

        $service = new CardReorderService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $this->createStub(BoardColumnRepository::class),
        );

        $this->expectException(AccessDeniedHttpException::class);
        $service->moveCard(1, 10, 0, $other);
    }

    public function testMoveCardWithinColumn(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);
        $cardA = $this->makeCard($column, 1, 0);
        $cardB = $this->makeCard($column, 2, 1);
        $cardC = $this->makeCard($column, 3, 2);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($cardA);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->expects($this->once())->method('flush');
        $entityManager->expects($this->once())->method('commit');

        $service = new CardReorderService(
            $entityManager,
            $cardRepo,
            $this->createStub(BoardColumnRepository::class),
        );

        $service->moveCard(1, 10, 2, $user);

        $this->assertSame(2, $cardA->getPosition());
        $this->assertSame(0, $cardB->getPosition());
        $this->assertSame(1, $cardC->getPosition());
    }

    public function testMoveCardBetweenColumns(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $sourceCol = $this->makeColumn($board, 10, 0);
        $targetCol = $this->makeColumn($board, 20, 1);
        $cardA = $this->makeCard($sourceCol, 1, 0);
        $cardB = $this->makeCard($sourceCol, 2, 1);
        $targetCard = $this->makeCard($targetCol, 3, 0);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($cardA);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardOwnerAndCards')->willReturn($targetCol);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->expects($this->once())->method('flush');
        $entityManager->expects($this->once())->method('commit');

        $service = new CardReorderService($entityManager, $cardRepo, $columnRepo);

        $service->moveCard(1, 20, 0, $user);

        $this->assertSame($targetCol, $cardA->getBoardColumn());
        $this->assertSame(0, $cardA->getPosition());
        $this->assertSame(1, $targetCard->getPosition());
        $this->assertSame(0, $cardB->getPosition());
    }

    public function testMoveCardTargetColumnNotFound(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);
        $card = $this->makeCard($column, 1);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($card);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardOwnerAndCards')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);

        $service = new CardReorderService($entityManager, $cardRepo, $columnRepo);

        $this->expectException(NotFoundHttpException::class);
        $service->moveCard(1, 999, 0, $user);
    }

    public function testMoveCardDifferentBoardThrows(): void
    {
        $user = $this->makeUser();
        $board1 = $this->makeBoard($user, 1);
        $board2 = $this->makeBoard($user, 2);
        $sourceCol = $this->makeColumn($board1, 10);
        $targetCol = $this->makeColumn($board2, 20);
        $card = $this->makeCard($sourceCol, 1);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($card);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardOwnerAndCards')->willReturn($targetCol);

        $service = new CardReorderService(
            $this->createStub(EntityManagerInterface::class),
            $cardRepo,
            $columnRepo,
        );

        $this->expectException(BadRequestHttpException::class);
        $service->moveCard(1, 20, 0, $user);
    }

    public function testMoveCardRollsBackOnException(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);
        $this->makeCard($column, 1, 0);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($column->getCards()->first());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->method('flush')->willThrowException(new \RuntimeException('DB error'));
        $entityManager->expects($this->once())->method('rollback');
        $entityManager->expects($this->never())->method('commit');

        $service = new CardReorderService($entityManager, $cardRepo, $this->createStub(BoardColumnRepository::class));

        $this->expectException(\RuntimeException::class);
        $service->moveCard(1, 10, 0, $user);
    }

    public function testRemoveCardAndReindex(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);
        $cardA = $this->makeCard($column, 1, 0);
        $cardB = $this->makeCard($column, 2, 1);
        $cardC = $this->makeCard($column, 3, 2);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($cardB);
        $entityManager->expects($this->once())->method('flush');

        $service = new CardReorderService(
            $entityManager,
            $this->createStub(CardRepository::class),
            $this->createStub(BoardColumnRepository::class),
        );

        $service->removeCardAndReindex($cardB);

        $this->assertSame(0, $cardA->getPosition());
        $this->assertSame(1, $cardC->getPosition());
    }

    public function testRemoveColumnAndReindex(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $colA = $this->makeColumn($board, 10, 0);
        $colB = $this->makeColumn($board, 20, 1);
        $colC = $this->makeColumn($board, 30, 2);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($colB);
        $entityManager->expects($this->once())->method('flush');

        $service = new CardReorderService(
            $entityManager,
            $this->createStub(CardRepository::class),
            $this->createStub(BoardColumnRepository::class),
        );

        $service->removeColumnAndReindex($colB);

        $this->assertSame(0, $colA->getPosition());
        $this->assertSame(1, $colC->getPosition());
    }

    public function testReorderColumnsSuccess(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $colA = $this->makeColumn($board, 10, 0);
        $colB = $this->makeColumn($board, 20, 1);
        $colC = $this->makeColumn($board, 30, 2);

        $boardRepo = $this->createStub(EntityRepository::class);
        $boardRepo->method('findOneBy')->willReturn($board);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findBy')->willReturn([$colA, $colB, $colC]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($boardRepo);
        $entityManager->expects($this->once())->method('flush');

        $service = new CardReorderService($entityManager, $this->createStub(CardRepository::class), $columnRepo);

        $service->reorderColumns('board-uuid', [30, 10, 20], $user);

        $this->assertSame(1, $colA->getPosition());
        $this->assertSame(2, $colB->getPosition());
        $this->assertSame(0, $colC->getPosition());
    }

    public function testReorderColumnsBoardNotFound(): void
    {
        $boardRepo = $this->createStub(EntityRepository::class);
        $boardRepo->method('findOneBy')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($boardRepo);

        $service = new CardReorderService(
            $entityManager,
            $this->createStub(CardRepository::class),
            $this->createStub(BoardColumnRepository::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $service->reorderColumns('missing-uuid', [1, 2], $this->makeUser());
    }

    public function testReorderColumnsAccessDenied(): void
    {
        $owner = $this->makeUser(1);
        $other = $this->makeUser(2);
        $board = $this->makeBoard($owner);
        $col = $this->makeColumn($board, 10);

        $boardRepo = $this->createStub(EntityRepository::class);
        $boardRepo->method('findOneBy')->willReturn($board);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findBy')->willReturn([$col]);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($boardRepo);

        $service = new CardReorderService($entityManager, $this->createStub(CardRepository::class), $columnRepo);

        $this->expectException(AccessDeniedHttpException::class);
        $service->reorderColumns('board-uuid', [10], $other);
    }

    public function testReorderColumnsInvalidColumnThrows(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $col = $this->makeColumn($board, 10);

        $boardRepo = $this->createStub(EntityRepository::class);
        $boardRepo->method('findOneBy')->willReturn($board);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findBy')->willReturn([$col]);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($boardRepo);

        $service = new CardReorderService($entityManager, $this->createStub(CardRepository::class), $columnRepo);

        $this->expectException(BadRequestHttpException::class);
        $service->reorderColumns('board-uuid', [10, 999], $user);
    }

    public function testReorderColumnsNoColumnsThrows(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);

        $boardRepo = $this->createStub(EntityRepository::class);
        $boardRepo->method('findOneBy')->willReturn($board);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findBy')->willReturn([]);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($boardRepo);

        $service = new CardReorderService($entityManager, $this->createStub(CardRepository::class), $columnRepo);

        $this->expectException(NotFoundHttpException::class);
        $service->reorderColumns('board-uuid', [], $user);
    }

    public function testMoveCardWithinColumnBackward(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);
        $cardA = $this->makeCard($column, 1, 0);
        $cardB = $this->makeCard($column, 2, 1);
        $cardC = $this->makeCard($column, 3, 2);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($cardC);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->expects($this->once())->method('flush');
        $entityManager->expects($this->once())->method('commit');

        $service = new CardReorderService($entityManager, $cardRepo, $this->createStub(BoardColumnRepository::class));

        $service->moveCard(3, 10, 0, $user);

        $this->assertSame(0, $cardC->getPosition());
        $this->assertSame(1, $cardA->getPosition());
        $this->assertSame(2, $cardB->getPosition());
    }

    public function testMoveCardSamePositionIsNoop(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);
        $card = $this->makeCard($column, 1, 0);

        $cardRepo = $this->createStub(CardRepository::class);
        $cardRepo->method('findWithColumnBoardAndOwner')->willReturn($card);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->expects($this->once())->method('flush');
        $entityManager->expects($this->once())->method('commit');

        $service = new CardReorderService($entityManager, $cardRepo, $this->createStub(BoardColumnRepository::class));

        $service->moveCard(1, 10, 0, $user);

        $this->assertSame(0, $card->getPosition());
    }
}
