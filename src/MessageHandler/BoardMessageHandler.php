<?php

namespace App\MessageHandler;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Message\CreateBoardMessage;
use App\Message\DeleteBoardMessage;
use App\Message\UpdateBoardMessage;
use App\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class BoardMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardRepository $boardRepository,
        private LoggerInterface $logger,
    ) {
    }

    #[AsMessageHandler]
    public function handleCreate(CreateBoardMessage $message): void
    {
        $existing = $this->boardRepository->findOneBy(['uuid' => $message->boardUuid]);
        if ($existing) {
            $this->logger->info('Board already exists, skipping create.', ['uuid' => $message->boardUuid]);
            return;
        }

        $owner = $this->entityManager->getReference(\App\Entity\User::class, $message->ownerId);

        $board = new Board();
        $board->setUuid($message->boardUuid);
        $board->setTitle($message->title);
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

        $this->logger->info('Board persisted.', ['uuid' => $message->boardUuid]);
    }

    #[AsMessageHandler]
    public function handleUpdate(UpdateBoardMessage $message): void
    {
        $board = $this->boardRepository->findOneBy(['uuid' => $message->boardUuid]);
        if (!$board) {
            $this->logger->warning('Board not found for update.', ['uuid' => $message->boardUuid]);
            return;
        }

        $board->setTitle($message->title);
        $this->entityManager->flush();
    }

    #[AsMessageHandler]
    public function handleDelete(DeleteBoardMessage $message): void
    {
        $board = $this->boardRepository->findOneBy(['uuid' => $message->boardUuid]);
        if (!$board) {
            $this->logger->info('Board already deleted.', ['uuid' => $message->boardUuid]);
            return;
        }

        $this->entityManager->remove($board);
        $this->entityManager->flush();
    }
}
