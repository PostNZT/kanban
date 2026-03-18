<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\JwtTokenHandler;
use App\Service\AuthenticationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationServiceTest extends TestCase
{
    public function testAuthenticateReturnsTokenAndUserOnSuccess(): void
    {
        $user = new User();
        $user->setEmail('auth@example.com');
        $user->setPassword('hashed_password');

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, 1);

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn($user);

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher
            ->method('isPasswordValid')
            ->willReturn(true);

        $jwtTokenHandler = $this->createStub(JwtTokenHandler::class);
        $jwtTokenHandler
            ->method('createToken')
            ->willReturn('jwt.token.here');

        $service = new AuthenticationService(
            $userRepository,
            $passwordHasher,
            $jwtTokenHandler,
        );

        $result = $service->authenticate('auth@example.com', 'correct_password');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame('jwt.token.here', $result['token']);
        $this->assertSame(1, $result['user']['id']);
        $this->assertSame('auth@example.com', $result['user']['email']);
    }

    public function testAuthenticateReturnsNullWhenUserNotFound(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->never())
            ->method('isPasswordValid');

        $jwtTokenHandler = $this->createMock(JwtTokenHandler::class);
        $jwtTokenHandler
            ->expects($this->never())
            ->method('createToken');

        $service = new AuthenticationService(
            $userRepository,
            $passwordHasher,
            $jwtTokenHandler,
        );

        $result = $service->authenticate('unknown@example.com', 'any_password');

        $this->assertNull($result);
    }

    public function testAuthenticateReturnsNullWhenPasswordInvalid(): void
    {
        $user = new User();
        $user->setEmail('auth@example.com');
        $user->setPassword('hashed_password');

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, 1);

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn($user);

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher
            ->method('isPasswordValid')
            ->willReturn(false);

        $jwtTokenHandler = $this->createMock(JwtTokenHandler::class);
        $jwtTokenHandler
            ->expects($this->never())
            ->method('createToken');

        $service = new AuthenticationService(
            $userRepository,
            $passwordHasher,
            $jwtTokenHandler,
        );

        $result = $service->authenticate('auth@example.com', 'wrong_password');

        $this->assertNull($result);
    }
}
