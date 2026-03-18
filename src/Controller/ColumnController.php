<?php

namespace App\Controller;

use App\Service\BoardSerializer;
use App\Service\CardReorderService;
use App\Service\ColumnService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ColumnController extends AbstractController
{
    public function __construct(
        private readonly ColumnService $columnService,
        private readonly CardReorderService $reorderService,
        private readonly BoardSerializer $boardSerializer,
    ) {
    }

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

        $column = $this->columnService->updateColumn($id, $title, $this->getUser());

        return $this->json($this->boardSerializer->serializeColumn($column));
    }

    #[Route('/api/columns/{id}', name: 'column_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->columnService->deleteColumn($id, $this->getUser());

        return $this->json(null, Response::HTTP_NO_CONTENT);
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

        $this->reorderService->reorderColumns($data['boardId'], $data['orderedColumnIds'], $this->getUser());

        return $this->json(['status' => 'ok']);
    }
}
