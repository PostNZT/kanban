<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Board;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserImplementsRequiredInterfaces(): void
    {
        $user = new User();
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class, $user);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testRolesAlwaysContainsRoleUser(): void
    {
        $user = new User();
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('foo@bar.com');
        $this->assertSame('foo@bar.com', $user->getEmail());
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $user->setPassword('hashed');
        $this->assertSame('hashed', $user->getPassword());
    }

    public function testBoardsCollection(): void
    {
        $user = new User();
        $board = new Board();
        $board->setTitle('Test Board');

        $user->addBoard($board);
        $this->assertCount(1, $user->getBoards());
        $this->assertSame($user, $board->getOwner());

        $user->removeBoard($board);
        $this->assertCount(0, $user->getBoards());
    }

    public function testAddBoardDoesNotDuplicate(): void
    {
        $user = new User();
        $board = new Board();
        $board->setTitle('Test');

        $user->addBoard($board);
        $user->addBoard($board);
        $this->assertCount(1, $user->getBoards());
    }
}
