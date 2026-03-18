<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;
use App\Tests\Functional\ApiTestCase;

class CardControllerTest extends ApiTestCase
{
    public function testCreateCard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $col = $this->em->getRepository(BoardColumn::class)->findOneBy(['title' => 'To Do']);

        $this->jsonRequest('POST', '/api/columns/' . $col->getId() . '/cards', [
            'title' => 'New Task',
            'description' => 'A new task description',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->getJsonResponse();
        $this->assertSame('New Task', $data['title']);
        $this->assertSame('A new task description', $data['description']);
        $this->assertSame(2, $data['position']); // 2 cards already exist at 0,1
    }

    public function testCreateCardDeniedForOtherUser(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user2@test.com');

        $col = $this->em->getRepository(BoardColumn::class)->findOneBy(['title' => 'To Do']);

        $this->jsonRequest('POST', '/api/columns/' . $col->getId() . '/cards', [
            'title' => 'Hack',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateCard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);

        $this->jsonRequest('PUT', '/api/cards/' . $card->getId(), [
            'title' => 'Updated Task',
            'description' => 'Updated description',
        ]);

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse();
        $this->assertSame('Updated Task', $data['title']);
        $this->assertSame('Updated description', $data['description']);
    }

    public function testDeleteCard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);

        $this->client->request('DELETE', '/api/cards/' . $card->getId());
        $this->assertResponseStatusCodeSame(204);
    }

    public function testMoveCardBetweenColumns(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);
        $targetCol = $this->em->getRepository(BoardColumn::class)->findOneBy(['title' => 'In Progress']);

        $this->jsonRequest('PUT', '/api/cards/move', [
            'cardId' => $card->getId(),
            'targetColumnId' => $targetCol->getId(),
            'targetPosition' => 0,
        ]);

        $this->assertResponseIsSuccessful();

        // Verify the card moved
        $this->em->clear();
        $movedCard = $this->em->getRepository(Card::class)->find($card->getId());
        $this->assertSame($targetCol->getId(), $movedCard->getBoardColumn()->getId());
        $this->assertSame(0, $movedCard->getPosition());
    }

    public function testMoveCardWithinSameColumn(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);
        $col = $card->getBoardColumn();

        $this->jsonRequest('PUT', '/api/cards/move', [
            'cardId' => $card->getId(),
            'targetColumnId' => $col->getId(),
            'targetPosition' => 1,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testMoveCardDeniedForOtherUser(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user2@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);
        $targetCol = $this->em->getRepository(BoardColumn::class)->findOneBy(['title' => 'In Progress']);

        $this->jsonRequest('PUT', '/api/cards/move', [
            'cardId' => $card->getId(),
            'targetColumnId' => $targetCol->getId(),
            'targetPosition' => 0,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCardRejectsInvalidJson(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $col = $this->em->getRepository(BoardColumn::class)->findOneBy(['title' => 'To Do']);

        $this->client->request(
            'POST',
            '/api/columns/' . $col->getId() . '/cards',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateCardRejectsTitleTooLong(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $col = $this->em->getRepository(BoardColumn::class)->findOneBy(['title' => 'To Do']);

        $this->jsonRequest('POST', '/api/columns/' . $col->getId() . '/cards', [
            'title' => str_repeat('A', 256),
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateCardWhitelistsFields(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);
        $originalPosition = $card->getPosition();

        $this->jsonRequest('PUT', '/api/cards/' . $card->getId(), [
            'title' => 'Updated Title',
            'position' => 999,
        ]);

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse();
        $this->assertSame('Updated Title', $data['title']);

        // Verify the extra 'position' field was ignored
        $this->em->clear();
        $refreshedCard = $this->em->getRepository(Card::class)->find($card->getId());
        $this->assertSame($originalPosition, $refreshedCard->getPosition());
    }

    public function testMoveCardRejectsInvalidTypes(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->jsonRequest('PUT', '/api/cards/move', [
            'cardId' => 'not-an-int',
            'targetColumnId' => 1,
            'targetPosition' => 0,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testMoveCardRejectsNegativePosition(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $card = $this->em->getRepository(Card::class)->findOneBy(['title' => 'Task 1']);
        $col = $card->getBoardColumn();

        $this->jsonRequest('PUT', '/api/cards/move', [
            'cardId' => $card->getId(),
            'targetColumnId' => $col->getId(),
            'targetPosition' => -1,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}
