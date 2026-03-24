<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CardService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardRepository $cardRepository,
        private readonly BoardColumnRepository $columnRepository,
    ) {
    }

    public function createCard(int $columnId, string $title, ?string $description, User $user): Card
    {
        $column = $this->columnRepository->findWithBoardAndOwner($columnId);
        if (!$column) {
            throw new NotFoundHttpException('Column not found.');
        }

        if (!$column->getBoard()->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $nextPosition = $this->columnRepository->getNextPosition($columnId);

        $card = new Card();
        $card->setTitle($title);
        $card->setDescription($description);
        $card->setPosition($nextPosition);
        $column->addCard($card);

        $this->entityManager->flush();

        return $card;
    }

    public function verifyCardOwnership(int $id, User $user): void
    {
        $card = $this->cardRepository->findWithColumnBoardAndOwner($id);
        if (!$card) {
            throw new NotFoundHttpException('Card not found.');
        }

        if (!$card->getBoardColumn()->getBoard()->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('Access denied.');
        }
    }
}
