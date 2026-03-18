<?php

namespace App\Tests\Unit\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\BoardRepository;
use App\Security\BoardVoter;
use App\Service\BoardService;
use App\Service\CardReorderService;
use App\Service\ColumnService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ColumnServiceTest extends TestCase
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

    public function testCreateColumnSuccess(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);

        $boardService = $this->createStub(BoardService::class);
        $boardService->method('getBoard')->willReturn($board);

        $boardRepo = $this->createStub(BoardRepository::class);
        $boardRepo->method('getNextColumnPosition')->willReturn(2);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = new ColumnService(
            $entityManager,
            $boardService,
            $boardRepo,
            $this->createStub(BoardColumnRepository::class),
            $this->createStub(CardReorderService::class),
        );

        $column = $service->createColumn('board-uuid', 'Review', $user);

        $this->assertSame('Review', $column->getTitle());
        $this->assertSame(2, $column->getPosition());
        $this->assertSame($board, $column->getBoard());
    }

    public function testUpdateColumnSuccess(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn($column);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = new ColumnService(
            $entityManager,
            $this->createStub(BoardService::class),
            $this->createStub(BoardRepository::class),
            $columnRepo,
            $this->createStub(CardReorderService::class),
        );

        $result = $service->updateColumn(10, 'Renamed', $user);
        $this->assertSame('Renamed', $result->getTitle());
    }

    public function testUpdateColumnNotFound(): void
    {
        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn(null);

        $service = new ColumnService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(BoardService::class),
            $this->createStub(BoardRepository::class),
            $columnRepo,
            $this->createStub(CardReorderService::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $service->updateColumn(999, 'Title', $this->makeUser());
    }

    public function testUpdateColumnAccessDenied(): void
    {
        $owner = $this->makeUser(1);
        $other = $this->makeUser(2);
        $board = $this->makeBoard($owner);
        $column = $this->makeColumn($board, 10);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn($column);

        $service = new ColumnService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(BoardService::class),
            $this->createStub(BoardRepository::class),
            $columnRepo,
            $this->createStub(CardReorderService::class),
        );

        $this->expectException(AccessDeniedHttpException::class);
        $service->updateColumn(10, 'Title', $other);
    }

    public function testDeleteColumnDelegatesToReorderService(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);
        $column = $this->makeColumn($board, 10);

        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn($column);

        $reorderService = $this->createMock(CardReorderService::class);
        $reorderService->expects($this->once())
            ->method('removeColumnAndReindex')
            ->with($column);

        $service = new ColumnService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(BoardService::class),
            $this->createStub(BoardRepository::class),
            $columnRepo,
            $reorderService,
        );

        $service->deleteColumn(10, $user);
    }

    public function testDeleteColumnNotFound(): void
    {
        $columnRepo = $this->createStub(BoardColumnRepository::class);
        $columnRepo->method('findWithBoardAndOwner')->willReturn(null);

        $service = new ColumnService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(BoardService::class),
            $this->createStub(BoardRepository::class),
            $columnRepo,
            $this->createStub(CardReorderService::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $service->deleteColumn(999, $this->makeUser());
    }
}
