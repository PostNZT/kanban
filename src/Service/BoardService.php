<?php

namespace App\Service;

use App\Entity\Board;
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
}
