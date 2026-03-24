<?php

namespace App\MessageHandler;

use App\Message\DeleteColumnMessage;
use App\Message\ReorderColumnsMessage;
use App\Message\UpdateColumnMessage;
use App\Repository\BoardColumnRepository;
use App\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class ColumnMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardColumnRepository $columnRepository,
        private BoardRepository $boardRepository,
        private LoggerInterface $logger,
    ) {
    }

    #[AsMessageHandler]
    public function handleUpdate(UpdateColumnMessage $message): void
    {
        $column = $this->columnRepository->find($message->columnId);
        if (!$column) {
            $this->logger->warning('Column not found for update.', ['id' => $message->columnId]);
            return;
        }

        $column->setTitle($message->title);
        $this->entityManager->flush();
    }

    #[AsMessageHandler]
    public function handleDelete(DeleteColumnMessage $message): void
    {
        $column = $this->columnRepository->findWithBoardAndOwner($message->columnId);
        if (!$column) {
            $this->logger->info('Column already deleted.', ['id' => $message->columnId]);
            return;
        }

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

    #[AsMessageHandler]
    public function handleReorder(ReorderColumnsMessage $message): void
    {
        $board = $this->boardRepository->findOneBy(['uuid' => $message->boardUuid]);
        if (!$board) {
            $this->logger->warning('Board not found for column reorder.', ['uuid' => $message->boardUuid]);
            return;
        }

        $columns = $this->columnRepository->findBy(['board' => $board]);
        $columnMap = [];
        foreach ($columns as $column) {
            $columnMap[$column->getId()] = $column;
        }

        foreach ($message->orderedColumnIds as $position => $columnId) {
            if (isset($columnMap[$columnId])) {
                $columnMap[$columnId]->setPosition($position);
            }
        }

        $this->entityManager->flush();
    }
}
