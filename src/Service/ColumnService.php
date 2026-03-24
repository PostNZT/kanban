<?php

namespace App\Service;

use App\Entity\BoardColumn;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ColumnService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BoardService $boardService,
        private readonly BoardRepository $boardRepository,
        private readonly BoardColumnRepository $columnRepository,
    ) {
    }

    public function createColumn(string $boardUuid, string $title, User $user): BoardColumn
    {
        $board = $this->boardService->getBoard($boardUuid, $user);
        $nextPosition = $this->boardRepository->getNextColumnPosition($board->getId());

        $column = new BoardColumn();
        $column->setTitle($title);
        $column->setPosition($nextPosition);
        $board->addColumn($column);

        $this->entityManager->flush();

        return $column;
    }

    public function verifyColumnOwnership(int $id, User $user): void
    {
        $column = $this->columnRepository->findWithBoardAndOwner($id);
        if (!$column) {
            throw new NotFoundHttpException('Column not found.');
        }

        if (!$column->getBoard()->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('Access denied.');
        }
    }
}
