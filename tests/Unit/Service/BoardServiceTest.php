<?php

namespace App\Tests\Unit\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Security\BoardVoter;
use App\Service\BoardService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BoardServiceTest extends TestCase
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

    public function testCreateBoardPersistsWithDefaultColumns(): void
    {
        $user = $this->makeUser();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Board::class));
        $entityManager->expects($this->once())->method('flush');

        $service = new BoardService(
            $entityManager,
            $this->createStub(BoardRepository::class),
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $board = $service->createBoard($user, 'My Board');

        $this->assertSame('My Board', $board->getTitle());
        $this->assertSame($user, $board->getOwner());
        $this->assertCount(3, $board->getColumns());

        $titles = array_map(fn(BoardColumn $c) => $c->getTitle(), $board->getColumns()->toArray());
        $this->assertSame(['To Do', 'In Progress', 'Done'], $titles);

        $positions = array_map(fn(BoardColumn $c) => $c->getPosition(), $board->getColumns()->toArray());
        $this->assertSame([0, 1, 2], $positions);
    }

    public function testGetBoardReturnsBoard(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn($board);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $service = new BoardService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $authChecker,
        );

        $result = $service->getBoard('some-uuid', $user);
        $this->assertSame($board, $result);
    }

    public function testGetBoardThrowsNotFoundWhenMissing(): void
    {
        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn(null);

        $service = new BoardService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $service->getBoard('missing-uuid', $this->makeUser());
    }

    public function testGetBoardThrowsAccessDenied(): void
    {
        $owner = $this->makeUser(1);
        $board = $this->makeBoard($owner);

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn($board);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(false);

        $service = new BoardService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $authChecker,
        );

        $this->expectException(AccessDeniedException::class);
        $service->getBoard('some-uuid', $this->makeUser(2));
    }

    public function testGetUserBoardsDelegatesToRepository(): void
    {
        $user = $this->makeUser();
        $boards = [$this->makeBoard($user, 1), $this->makeBoard($user, 2)];

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findBy')->willReturn($boards);

        $service = new BoardService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $result = $service->getUserBoards($user);
        $this->assertCount(2, $result);
    }

    public function testUpdateBoardChangesTitle(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn($board);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = new BoardService($entityManager, $repository, $authChecker);

        $result = $service->updateBoard('some-uuid', $user, 'New Title');
        $this->assertSame('New Title', $result->getTitle());
    }

    public function testUpdateBoardThrowsAccessDeniedOnEdit(): void
    {
        $owner = $this->makeUser(1);
        $board = $this->makeBoard($owner);

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn($board);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')
            ->willReturnCallback(function (string $attribute) {
                return $attribute === BoardVoter::VIEW;
            });

        $service = new BoardService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $authChecker,
        );

        $this->expectException(AccessDeniedException::class);
        $service->updateBoard('some-uuid', $owner, 'New Title');
    }

    public function testDeleteBoardRemovesAndFlushes(): void
    {
        $user = $this->makeUser();
        $board = $this->makeBoard($user);

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn($board);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($board);
        $entityManager->expects($this->once())->method('flush');

        $service = new BoardService($entityManager, $repository, $authChecker);
        $service->deleteBoard('some-uuid', $user);
    }

    public function testDeleteBoardThrowsAccessDeniedOnDelete(): void
    {
        $owner = $this->makeUser(1);
        $board = $this->makeBoard($owner);

        $repository = $this->createStub(BoardRepository::class);
        $repository->method('findWithColumnsAndCards')->willReturn($board);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')
            ->willReturnCallback(function (string $attribute) {
                return $attribute === BoardVoter::VIEW;
            });

        $service = new BoardService(
            $this->createStub(EntityManagerInterface::class),
            $repository,
            $authChecker,
        );

        $this->expectException(AccessDeniedException::class);
        $service->deleteBoard('some-uuid', $owner);
    }
}
