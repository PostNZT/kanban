<?php

namespace App\MessageHandler;

use App\Message\DeleteCardMessage;
use App\Message\MoveCardMessage;
use App\Message\UpdateCardMessage;
use App\Repository\BoardColumnRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class CardMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CardRepository $cardRepository,
        private BoardColumnRepository $columnRepository,
        private LoggerInterface $logger,
    ) {
    }

    #[AsMessageHandler]
    public function handleUpdate(UpdateCardMessage $message): void
    {
        $card = $this->cardRepository->find($message->cardId);
        if (!$card) {
            $this->logger->warning('Card not found for update.', ['id' => $message->cardId]);
            return;
        }

        if ($message->title !== null) {
            $card->setTitle($message->title);
        }
        if ($message->descriptionProvided) {
            $card->setDescription($message->description);
        }

        $this->entityManager->flush();
    }

    #[AsMessageHandler]
    public function handleDelete(DeleteCardMessage $message): void
    {
        $card = $this->cardRepository->findWithColumnBoardAndOwner($message->cardId);
        if (!$card) {
            $this->logger->info('Card already deleted.', ['id' => $message->cardId]);
            return;
        }

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

    #[AsMessageHandler]
    public function handleMove(MoveCardMessage $message): void
    {
        $card = $this->cardRepository->findWithColumnBoardAndOwner($message->cardId);
        if (!$card) {
            $this->logger->warning('Card not found for move.', ['id' => $message->cardId]);
            return;
        }

        $sourceColumn = $card->getBoardColumn();
        $targetPosition = $message->targetPosition;

        if ($sourceColumn->getId() === $message->targetColumnId) {
            $this->moveWithinColumn($card, $targetPosition);
        } else {
            $targetColumn = $this->columnRepository->findWithBoardOwnerAndCards($message->targetColumnId);
            if (!$targetColumn) {
                $this->logger->warning('Target column not found for move.', ['id' => $message->targetColumnId]);
                return;
            }
            $this->moveBetweenColumns($card, $sourceColumn, $targetColumn, $targetPosition);
        }

        $this->entityManager->flush();
    }

    private function moveWithinColumn(\App\Entity\Card $card, int $targetPosition): void
    {
        $column = $card->getBoardColumn();
        $currentPosition = $card->getPosition();

        if ($currentPosition === $targetPosition) {
            return;
        }

        foreach ($column->getCards() as $sibling) {
            if ($sibling->getId() === $card->getId()) {
                continue;
            }
            $pos = $sibling->getPosition();
            if ($currentPosition < $targetPosition && $pos > $currentPosition && $pos <= $targetPosition) {
                $sibling->setPosition($pos - 1);
            } elseif ($currentPosition > $targetPosition && $pos >= $targetPosition && $pos < $currentPosition) {
                $sibling->setPosition($pos + 1);
            }
        }

        $card->setPosition($targetPosition);
    }

    private function moveBetweenColumns(
        \App\Entity\Card $card,
        \App\Entity\BoardColumn $sourceColumn,
        \App\Entity\BoardColumn $targetColumn,
        int $targetPosition,
    ): void {
        $oldPosition = $card->getPosition();

        foreach ($sourceColumn->getCards() as $sibling) {
            if ($sibling->getId() === $card->getId()) {
                continue;
            }
            if ($sibling->getPosition() > $oldPosition) {
                $sibling->setPosition($sibling->getPosition() - 1);
            }
        }

        foreach ($targetColumn->getCards() as $existing) {
            if ($existing->getPosition() >= $targetPosition) {
                $existing->setPosition($existing->getPosition() + 1);
            }
        }

        $sourceColumn->removeCard($card);
        $card->setBoardColumn($targetColumn);
        $card->setPosition($targetPosition);
        $targetColumn->addCard($card);
    }
}
