<?php

namespace App\Controller;

use App\Message\DeleteCardMessage;
use App\Message\MoveCardMessage;
use App\Message\UpdateCardMessage;
use App\Service\BoardSerializer;
use App\Service\CardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class CardController extends AbstractController
{
    public function __construct(
        private readonly CardService $cardService,
        private readonly BoardSerializer $boardSerializer,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    // Card create stays synchronous — single INSERT, needs real ID back
    #[Route('/api/columns/{columnId}/cards', name: 'card_create', methods: ['POST'])]
    public function create(int $columnId, Request $request): JsonResponse
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

        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;
        if ($description !== null && mb_strlen($description) > 5000) {
            return $this->json(['error' => 'Description must not exceed 5000 characters.'], Response::HTTP_BAD_REQUEST);
        }

        $card = $this->cardService->createCard(
            $columnId,
            $title,
            $description,
            $this->getUser(),
        );

        return $this->json($this->boardSerializer->serializeCard($card), Response::HTTP_CREATED);
    }

    #[Route('/api/cards/{id}', name: 'card_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $allowed = array_intersect_key($data, array_flip(['title', 'description']));

        if (isset($allowed['title'])) {
            if (!is_string($allowed['title']) || trim($allowed['title']) === '') {
                return $this->json(['error' => 'Title must be a non-empty string.'], Response::HTTP_BAD_REQUEST);
            }
            $allowed['title'] = trim($allowed['title']);
            if (mb_strlen($allowed['title']) > 255) {
                return $this->json(['error' => 'Title must not exceed 255 characters.'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (array_key_exists('description', $allowed) && $allowed['description'] !== null) {
            if (!is_string($allowed['description'])) {
                return $this->json(['error' => 'Description must be a string or null.'], Response::HTTP_BAD_REQUEST);
            }
            if (mb_strlen($allowed['description']) > 5000) {
                return $this->json(['error' => 'Description must not exceed 5000 characters.'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Verify ownership
        $this->cardService->verifyCardOwnership($id, $this->getUser());

        // Dispatch async update
        $this->messageBus->dispatch(new UpdateCardMessage(
            cardId: $id,
            title: $allowed['title'] ?? null,
            description: $allowed['description'] ?? null,
            descriptionProvided: array_key_exists('description', $allowed),
        ));

        return $this->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/cards/{id}', name: 'card_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        // Verify ownership
        $this->cardService->verifyCardOwnership($id, $this->getUser());

        // Dispatch async delete
        $this->messageBus->dispatch(new DeleteCardMessage(cardId: $id));

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/api/cards/move', name: 'card_move', methods: ['PUT'])]
    public function move(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['cardId'], $data['targetColumnId'], $data['targetPosition'])) {
            return $this->json(
                ['error' => 'cardId, targetColumnId, and targetPosition are required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (!is_int($data['cardId']) || !is_int($data['targetColumnId']) || !is_int($data['targetPosition'])) {
            return $this->json(['error' => 'cardId, targetColumnId, and targetPosition must be integers.'], Response::HTTP_BAD_REQUEST);
        }

        if ($data['targetPosition'] < 0) {
            return $this->json(['error' => 'targetPosition must be non-negative.'], Response::HTTP_BAD_REQUEST);
        }

        // Verify ownership
        $this->cardService->verifyCardOwnership($data['cardId'], $this->getUser());

        // Dispatch async move
        $this->messageBus->dispatch(new MoveCardMessage(
            cardId: $data['cardId'],
            targetColumnId: $data['targetColumnId'],
            targetPosition: $data['targetPosition'],
        ));

        return $this->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }
}
