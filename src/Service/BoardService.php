<?php

namespace App\Service;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Security\BoardVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BoardService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BoardRepository $boardRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function createBoard(User $owner, string $title): Board
    {
        $board = new Board();
        $board->setTitle($title);
        $board->setOwner($owner);

        $defaultColumns = ['To Do', 'In Progress', 'Done'];
        foreach ($defaultColumns as $position => $columnTitle) {
            $column = new BoardColumn();
            $column->setTitle($columnTitle);
            $column->setPosition($position);
            $board->addColumn($column);
        }

        $this->entityManager->persist($board);
        $this->entityManager->flush();

        return $board;
    }

    public function getBoard(string $uuid, User $user): Board
    {
        $board = $this->boardRepository->findWithColumnsAndCards($uuid);

        if (!$board) {
            throw new NotFoundHttpException('Board not found.');
        }

        if (!$this->authorizationChecker->isGranted(BoardVoter::VIEW, $board)) {
            throw new AccessDeniedException('Access denied.');
        }

        return $board;
    }

    public function getUserBoards(User $user): array
    {
        return $this->boardRepository->findBy(['owner' => $user], ['createdAt' => 'DESC']);
    }

    public function updateBoard(string $uuid, User $user, string $title): Board
    {
        $board = $this->getBoard($uuid, $user);

        if (!$this->authorizationChecker->isGranted(BoardVoter::EDIT, $board)) {
            throw new AccessDeniedException('Access denied.');
        }

        $board->setTitle($title);
        $this->entityManager->flush();

        return $board;
    }

    public function deleteBoard(string $uuid, User $user): void
    {
        $board = $this->getBoard($uuid, $user);

        if (!$this->authorizationChecker->isGranted(BoardVoter::DELETE, $board)) {
            throw new AccessDeniedException('Access denied.');
        }

        $this->entityManager->remove($board);
        $this->entityManager->flush();
    }
}
