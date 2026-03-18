<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiTestCase;

class AuthControllerTest extends ApiTestCase
{
    public function testRegisterCreatesUser(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'newuser@test.com',
            'password' => 'Password1',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->getJsonResponse();
        $this->assertSame('newuser@test.com', $data['email']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testRegisterRejectsWeakPassword(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'weakpw@test.com',
            'password' => 'short',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'not-an-email',
            'password' => 'Password1',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->loadFixtures();

        $this->jsonRequest('POST', '/api/register', [
            'email' => 'user1@test.com',
            'password' => 'Password1',
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterRejectsMissingFields(): void
    {
        $this->jsonRequest('POST', '/api/register', []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginReturnsUserAndSetsCookie(): void
    {
        $this->loadFixtures();

        $this->jsonRequest('POST', '/api/login', [
            'email' => 'user1@test.com',
            'password' => 'password123',
        ]);

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('user', $data);
        $this->assertSame('user1@test.com', $data['user']['email']);

        $cookie = $this->client->getCookieJar()->get('jwt_token');
        $this->assertNotNull($cookie, 'JWT cookie should be set');
        $this->assertTrue($cookie->isHttpOnly(), 'JWT cookie should be httpOnly');
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $this->loadFixtures();

        $this->jsonRequest('POST', '/api/login', [
            'email' => 'user1@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginRejectsMissingFields(): void
    {
        $this->jsonRequest('POST', '/api/login', []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLogoutClearsCookie(): void
    {
        $this->loadFixtures();

        // Login first to get a cookie
        $this->jsonRequest('POST', '/api/login', [
            'email' => 'user1@test.com',
            'password' => 'password123',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->client->getCookieJar()->get('jwt_token'));

        // Now logout
        $this->jsonRequest('POST', '/api/logout', []);

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse();
        $this->assertSame('Logged out.', $data['message']);

        // Cookie should be expired (cleared)
        $cookie = $this->client->getCookieJar()->get('jwt_token');
        $this->assertTrue(
            $cookie === null || $cookie->isExpired(),
            'JWT cookie should be cleared after logout',
        );
    }

    public function testRegisterRejectsPasswordWithoutUppercase(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'nouppercase@test.com',
            'password' => 'password1',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $data = $this->getJsonResponse();
        $this->assertNotEmpty($data['errors']);
    }

    public function testRegisterRejectsPasswordWithoutNumber(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'nonumber@test.com',
            'password' => 'Passwordx',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $data = $this->getJsonResponse();
        $this->assertNotEmpty($data['errors']);
    }

    public function testRegisterRejectsPasswordTooShort(): void
    {
        $this->jsonRequest('POST', '/api/register', [
            'email' => 'tooshort@test.com',
            'password' => 'Pa1',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $data = $this->getJsonResponse();
        $this->assertNotEmpty($data['errors']);
    }

    public function testLoginRejectsInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json',
        );

        $this->assertResponseStatusCodeSame(400);
        $data = $this->getJsonResponse();
        $this->assertSame('Invalid JSON.', $data['error']);
    }

    public function testRegisterRejectsInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json',
        );

        $this->assertResponseStatusCodeSame(400);
        $data = $this->getJsonResponse();
        $this->assertSame('Invalid JSON.', $data['error']);
    }
}
