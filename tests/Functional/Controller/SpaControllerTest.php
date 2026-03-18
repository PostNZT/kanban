<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiTestCase;

class SpaControllerTest extends ApiTestCase
{
    public function testSwaggerPageReturnsOk(): void
    {
        $this->client->request('GET', '/api/doc');
        $this->assertResponseIsSuccessful();
    }

    public function testSpaPageReturnsOk(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testSpaCatchAllReturnsOk(): void
    {
        $this->client->request('GET', '/some/random/path');
        $this->assertResponseIsSuccessful();
    }
}
