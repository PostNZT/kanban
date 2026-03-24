<?php

namespace App\Controller;

use App\Message\CreateBoardMessage;
use App\Message\DeleteBoardMessage;
use App\Message\UpdateBoardMessage;
use App\Service\BoardSerializer;
use App\Service\BoardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/boards')]
class BoardController extends AbstractController
{
    public function __construct(
        private readonly BoardService $boardService,
        private readonly BoardSerializer $boardSerializer,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', name: 'board_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        $boards = $this->boardService->getUserBoards($user);

        $data = array_map(
            fn($board) => $this->boardSerializer->serializeBoardSummary($board),
            $boards,
        );

        return $this->json($data);
    }

    #[Route('', name: 'board_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['title']) || !is_string($data['title']) || trim($data['title']) === '') {
            return $this->json(['error' => 'Title is required.'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim($data['title']);
        if (mb_strlen($title) > 255) {
            return $this->json(['error' => 'Title must not exceed 255 characters.'], Response::HTTP_BAD_REQUEST);
        }

        // Accept client-provided UUID or generate one
        $boardUuid = isset($data['uuid']) && is_string($data['uuid']) ? $data['uuid'] : Uuid::v4()->toRfc4122();

        // Dispatch async — DB write happens in background
        $this->messageBus->dispatch(new CreateBoardMessage(
            boardUuid: $boardUuid,
            ownerId: $user->getId(),
            title: $title,
        ));

        // Return optimistic response immediately (no DB hit)
        $now = new \DateTimeImmutable();
        return $this->json([
            'id' => $boardUuid,
            'title' => $title,
            'columns' => [
                ['id' => -1, 'title' => 'To Do', 'position' => 0, 'cards' => []],
                ['id' => -2, 'title' => 'In Progress', 'position' => 1, 'cards' => []],
                ['id' => -3, 'title' => 'Done', 'position' => 2, 'cards' => []],
            ],
            'createdAt' => $now->format('c'),
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{uuid}', name: 'board_show', methods: ['GET'], requirements: ['uuid' => '[0-9a-f-]{36}'])]
    public function show(string $uuid): JsonResponse
    {
        $user = $this->getUser();
        $board = $this->boardService->getBoard($uuid, $user);

        return $this->json($this->boardSerializer->serializeBoard($board));
    }

    #[Route('/{uuid}', name: 'board_update', methods: ['PUT'], requirements: ['uuid' => '[0-9a-f-]{36}'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['title']) || !is_string($data['title']) || trim($data['title']) === '') {
            return $this->json(['error' => 'Title is required.'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim($data['title']);
        if (mb_strlen($title) > 255) {
            return $this->json(['error' => 'Title must not exceed 255 characters.'], Response::HTTP_BAD_REQUEST);
        }

        // Verify ownership before dispatching
        $this->boardService->getBoard($uuid, $user);

        // Dispatch async update
        $this->messageBus->dispatch(new UpdateBoardMessage(
            boardUuid: $uuid,
            title: $title,
        ));

        return $this->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }

    #[Route('/{uuid}', name: 'board_delete', methods: ['DELETE'], requirements: ['uuid' => '[0-9a-f-]{36}'])]
    public function delete(string $uuid): JsonResponse
    {
        $user = $this->getUser();

        // Verify ownership before dispatching
        $this->boardService->getBoard($uuid, $user);

        // Dispatch async delete
        $this->messageBus->dispatch(new DeleteBoardMessage(boardUuid: $uuid));

        return $this->json(null, Response::HTTP_ACCEPTED);
    }
}
