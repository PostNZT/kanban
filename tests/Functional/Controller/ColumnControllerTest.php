<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Tests\Functional\ApiTestCase;

class ColumnControllerTest extends ApiTestCase
{
    public function testCreateColumn(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->jsonRequest('POST', '/api/boards/' . $boardUuid . '/columns', [
            'title' => 'Review',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->getJsonResponse();
        $this->assertSame('Review', $data['title']);
        $this->assertSame(3, $data['position']);
    }

    public function testCreateColumnDeniedForOtherUser(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user2@test.com');

        $boardUuid = $this->em->getRepository(Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->jsonRequest('POST', '/api/boards/' . $boardUuid . '/columns', [
            'title' => 'Hack',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateColumn(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $colId = $this->em->getRepository(BoardColumn::class)
            ->findOneBy(['title' => 'To Do'])->getId();

        $this->jsonRequest('PUT', '/api/columns/' . $colId, [
            'title' => 'Backlog',
        ]);

        $this->assertResponseStatusCodeSame(202);
        $data = $this->getJsonResponse();
        $this->assertSame('accepted', $data['status']);
    }

    public function testDeleteColumn(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $colId = $this->em->getRepository(BoardColumn::class)
            ->findOneBy(['title' => 'To Do'])->getId();

        $this->client->request('DELETE', '/api/columns/' . $colId);
        $this->assertResponseStatusCodeSame(202);
    }

    public function testReorderColumns(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $board = $this->em->getRepository(Board::class)->findOneBy(['title' => 'Test Board']);
        $columns = $this->em->getRepository(BoardColumn::class)->findBy(
            ['board' => $board],
            ['position' => 'ASC'],
        );

        $ids = array_map(fn($c) => $c->getId(), $columns);
        $reversed = array_reverse($ids);

        $this->jsonRequest('PUT', '/api/columns/reorder', [
            'boardId' => $board->getUuid(),
            'orderedColumnIds' => $reversed,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testCreateColumnRejectsInvalidJson(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->client->request(
            'POST',
            '/api/boards/' . $boardUuid . '/columns',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateColumnRejectsTitleTooLong(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->jsonRequest('POST', '/api/boards/' . $boardUuid . '/columns', [
            'title' => str_repeat('A', 256),
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testReorderColumnsRejectsInvalidTypes(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $board = $this->em->getRepository(Board::class)->findOneBy(['title' => 'Test Board']);

        $this->jsonRequest('PUT', '/api/columns/reorder', [
            'boardId' => $board->getUuid(),
            'orderedColumnIds' => ['not-an-int', 'also-not-an-int'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}
