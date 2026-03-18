<?php

namespace App\Controller;

use App\Service\BoardSerializer;
use App\Service\BoardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/boards')]
class BoardController extends AbstractController
{
    public function __construct(
        private readonly BoardService $boardService,
        private readonly BoardSerializer $boardSerializer,
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

        $board = $this->boardService->createBoard($user, $title);

        return $this->json($this->boardSerializer->serializeBoard($board), Response::HTTP_CREATED);
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

        $board = $this->boardService->updateBoard($uuid, $user, $title);

        return $this->json($this->boardSerializer->serializeBoard($board));
    }

    #[Route('/{uuid}', name: 'board_delete', methods: ['DELETE'], requirements: ['uuid' => '[0-9a-f-]{36}'])]
    public function delete(string $uuid): JsonResponse
    {
        $user = $this->getUser();
        $this->boardService->deleteBoard($uuid, $user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
