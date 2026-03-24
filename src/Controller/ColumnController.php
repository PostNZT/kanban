<?php

namespace App\Controller;

use App\Message\DeleteColumnMessage;
use App\Message\ReorderColumnsMessage;
use App\Message\UpdateColumnMessage;
use App\Service\BoardSerializer;
use App\Service\ColumnService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ColumnController extends AbstractController
{
    public function __construct(
        private readonly ColumnService $columnService,
        private readonly BoardSerializer $boardSerializer,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    // Column create stays synchronous — single INSERT, needs real ID back
    #[Route('/api/boards/{boardUuid}/columns', name: 'column_create', methods: ['POST'], requirements: ['boardUuid' => '[0-9a-f-]{36}'])]
    public function create(string $boardUuid, Request $request): JsonResponse
    {
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

        $column = $this->columnService->createColumn($boardUuid, $title, $this->getUser());

        return $this->json($this->boardSerializer->serializeColumn($column), Response::HTTP_CREATED);
    }

    #[Route('/api/columns/{id}', name: 'column_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
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

        // Verify ownership
        $this->columnService->verifyColumnOwnership($id, $this->getUser());

        // Dispatch async update
        $this->messageBus->dispatch(new UpdateColumnMessage(columnId: $id, title: $title));

        return $this->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/columns/{id}', name: 'column_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        // Verify ownership
        $this->columnService->verifyColumnOwnership($id, $this->getUser());

        // Dispatch async delete
        $this->messageBus->dispatch(new DeleteColumnMessage(columnId: $id));

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/api/columns/reorder', name: 'column_reorder', methods: ['PUT'])]
    public function reorder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['boardId'], $data['orderedColumnIds'])) {
            return $this->json(['error' => 'boardId and orderedColumnIds are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($data['boardId']) || !is_array($data['orderedColumnIds'])) {
            return $this->json(['error' => 'boardId must be a string and orderedColumnIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($data['orderedColumnIds'] as $columnId) {
            if (!is_int($columnId)) {
                return $this->json(['error' => 'All column IDs must be integers.'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Dispatch async reorder
        $this->messageBus->dispatch(new ReorderColumnsMessage(
            boardUuid: $data['boardId'],
            orderedColumnIds: $data['orderedColumnIds'],
        ));

        return $this->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }
}
