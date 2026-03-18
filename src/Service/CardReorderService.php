<?php

namespace App\Service;

use App\Entity\BoardColumn;
use App\Entity\Card;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CardReorderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardRepository $cardRepository,
        private readonly BoardColumnRepository $columnRepository,
    ) {
    }

    public function moveCard(int $cardId, int $targetColumnId, int $targetPosition, User $user): void
    {
        $card = $this->cardRepository->findWithColumnBoardAndOwner($cardId);
        if (!$card) {
            throw new NotFoundHttpException('Card not found.');
        }

        $sourceColumn = $card->getBoardColumn();
        $sourceBoard = $sourceColumn->getBoard();

        if (!$sourceBoard->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        if ($sourceColumn->getId() === $targetColumnId) {
            $targetColumn = $sourceColumn;
        } else {
            $targetColumn = $this->columnRepository->findWithBoardOwnerAndCards($targetColumnId);
            if (!$targetColumn) {
                throw new NotFoundHttpException('Target column not found.');
            }
            if ($sourceBoard->getId() !== $targetColumn->getBoard()->getId()) {
                throw new BadRequestHttpException('Card and target column must belong to the same board.');
            }
        }

        $this->entityManager->beginTransaction();
        try {
            if ($sourceColumn->getId() === $targetColumn->getId()) {
                $this->moveWithinColumn($card, $targetPosition);
            } else {
                $this->moveBetweenColumns($card, $sourceColumn, $targetColumn, $targetPosition);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    public function removeCardAndReindex(Card $card): void
    {
        $column = $card->getBoardColumn();
        $removedPosition = $card->getPosition();
        $column->removeCard($card);
        $this->entityManager->remove($card);

        foreach ($column->getCards() as $sibling) {
            if ($sibling->getPosition() > $removedPosition) {
                $sibling->setPosition($sibling->getPosition() - 1);
            }
        }

        $this->entityManager->flush();
    }

    public function removeColumnAndReindex(BoardColumn $column): void
    {
        $board = $column->getBoard();
        $removedPosition = $column->getPosition();
        $board->removeColumn($column);
        $this->entityManager->remove($column);

        foreach ($board->getColumns() as $sibling) {
            if ($sibling->getPosition() > $removedPosition) {
                $sibling->setPosition($sibling->getPosition() - 1);
            }
        }

        $this->entityManager->flush();
    }

    public function reorderColumns(string $boardUuid, array $orderedColumnIds, User $user): void
    {
        $board = $this->entityManager->getRepository(\App\Entity\Board::class)->findOneBy(['uuid' => $boardUuid]);
        if (!$board) {
            throw new NotFoundHttpException('Board not found.');
        }

        $columns = $this->columnRepository->findBy(['board' => $board]);

        if (empty($columns)) {
            throw new NotFoundHttpException('No columns found for this board.');
        }

        $board = $columns[0]->getBoard();
        if (!$board->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $columnMap = [];
        foreach ($columns as $column) {
            $columnMap[$column->getId()] = $column;
        }

        foreach ($orderedColumnIds as $position => $columnId) {
            if (!isset($columnMap[$columnId])) {
                throw new BadRequestHttpException("Column $columnId does not belong to this board.");
            }
            $columnMap[$columnId]->setPosition($position);
        }

        $this->entityManager->flush();
    }

    private function moveWithinColumn(Card $card, int $targetPosition): void
    {
        $column = $card->getBoardColumn();
        $currentPosition = $card->getPosition();

        if ($currentPosition === $targetPosition) {
            return;
        }

        $cards = $column->getCards()->toArray();
        usort($cards, fn(Card $first, Card $second) => $first->getPosition() - $second->getPosition());

        if ($currentPosition < $targetPosition) {
            foreach ($cards as $sibling) {
                if ($sibling->getId() === $card->getId()) {
                    continue;
                }
                $siblingPosition = $sibling->getPosition();
                if ($siblingPosition > $currentPosition && $siblingPosition <= $targetPosition) {
                    $sibling->setPosition($siblingPosition - 1);
                }
            }
        } else {
            foreach ($cards as $sibling) {
                if ($sibling->getId() === $card->getId()) {
                    continue;
                }
                $siblingPosition = $sibling->getPosition();
                if ($siblingPosition >= $targetPosition && $siblingPosition < $currentPosition) {
                    $sibling->setPosition($siblingPosition + 1);
                }
            }
        }

        $card->setPosition($targetPosition);
    }

    private function moveBetweenColumns(Card $card, BoardColumn $sourceColumn, BoardColumn $targetColumn, int $targetPosition): void
    {
        $oldPosition = $card->getPosition();

        foreach ($sourceColumn->getCards() as $sibling) {
            if ($sibling->getId() === $card->getId()) {
                continue;
            }
            if ($sibling->getPosition() > $oldPosition) {
                $sibling->setPosition($sibling->getPosition() - 1);
            }
        }

        foreach ($targetColumn->getCards() as $existingCard) {
            if ($existingCard->getPosition() >= $targetPosition) {
                $existingCard->setPosition($existingCard->getPosition() + 1);
            }
        }

        $sourceColumn->removeCard($card);
        $card->setBoardColumn($targetColumn);
        $card->setPosition($targetPosition);
        $targetColumn->addCard($card);
    }
}
