<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Security\JwtTokenHandler;
use App\Tests\DataFixtures\TestFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Recreate schema
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function loadFixtures(): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $fixture = new TestFixtures($passwordHasher);

        $loader = new Loader();
        $loader->addFixture($fixture);

        $purger = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());
    }

    protected function authenticateAs(string $email): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        $jwtHandler = static::getContainer()->get(JwtTokenHandler::class);
        $token = $jwtHandler->createToken($user->getId(), $user->getEmail());

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
    }

    protected function jsonRequest(string $method, string $uri, array $data = []): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data),
        );
    }

    protected function getJsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }
}
