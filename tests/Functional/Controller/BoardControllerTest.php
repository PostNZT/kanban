<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiTestCase;

class BoardControllerTest extends ApiTestCase
{
    public function testListBoardsRequiresAuth(): void
    {
        $this->client->request('GET', '/api/boards');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListBoardsReturnsOnlyOwnedBoards(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->client->request('GET', '/api/boards');
        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertCount(1, $data);
        $this->assertSame('Test Board', $data[0]['title']);
    }

    public function testCreateBoard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->jsonRequest('POST', '/api/boards', ['title' => 'New Board']);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->getJsonResponse();
        $this->assertSame('New Board', $data['title']);
        $this->assertCount(3, $data['columns']);
        $this->assertSame('To Do', $data['columns'][0]['title']);
        $this->assertSame('In Progress', $data['columns'][1]['title']);
        $this->assertSame('Done', $data['columns'][2]['title']);
    }

    public function testCreateBoardRejectsEmptyTitle(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->jsonRequest('POST', '/api/boards', ['title' => '']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testShowBoard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(\App\Entity\Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->client->request('GET', '/api/boards/' . $boardUuid);
        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertSame('Test Board', $data['title']);
        $this->assertCount(3, $data['columns']);
    }

    public function testShowBoardDeniedForOtherUser(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user2@test.com');

        $boardUuid = $this->em->getRepository(\App\Entity\Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->client->request('GET', '/api/boards/' . $boardUuid);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateBoard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(\App\Entity\Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->jsonRequest('PUT', '/api/boards/' . $boardUuid, ['title' => 'Updated Board']);
        $this->assertResponseStatusCodeSame(202);

        $data = $this->getJsonResponse();
        $this->assertSame('accepted', $data['status']);
    }

    public function testDeleteBoard(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(\App\Entity\Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->client->request('DELETE', '/api/boards/' . $boardUuid);
        $this->assertResponseStatusCodeSame(202);
    }

    public function testBoardIdIsUuidNotInteger(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->client->request('GET', '/api/boards');
        $data = $this->getJsonResponse();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $data[0]['id'],
        );
    }

    public function testCreateBoardRejectsInvalidJson(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->client->request(
            'POST',
            '/api/boards',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateBoardRejectsTitleTooLong(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $this->jsonRequest('POST', '/api/boards', ['title' => str_repeat('A', 256)]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateBoardRejectsInvalidJson(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(\App\Entity\Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->client->request(
            'PUT',
            '/api/boards/' . $boardUuid,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateBoardRejectsTitleTooLong(): void
    {
        $this->loadFixtures();
        $this->authenticateAs('user1@test.com');

        $boardUuid = $this->em->getRepository(\App\Entity\Board::class)
            ->findOneBy(['title' => 'Test Board'])->getUuid();

        $this->jsonRequest('PUT', '/api/boards/' . $boardUuid, ['title' => str_repeat('A', 256)]);

        $this->assertResponseStatusCodeSame(400);
    }
}
