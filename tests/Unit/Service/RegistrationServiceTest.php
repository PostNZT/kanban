<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationServiceTest extends TestCase
{
    public function testIsEmailTakenReturnsTrueWhenExists(): void
    {
        $user = new User();
        $user->setEmail('taken@example.com');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn($user);

        $service = new RegistrationService(
            $this->createStub(EntityManagerInterface::class),
            $userRepository,
            $this->createStub(UserPasswordHasherInterface::class),
            $this->createStub(ValidatorInterface::class),
        );

        $this->assertTrue($service->isEmailTaken('taken@example.com'));
    }

    public function testIsEmailTakenReturnsFalseWhenNotExists(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $service = new RegistrationService(
            $this->createStub(EntityManagerInterface::class),
            $userRepository,
            $this->createStub(UserPasswordHasherInterface::class),
            $this->createStub(ValidatorInterface::class),
        );

        $this->assertFalse($service->isEmailTaken('free@example.com'));
    }

    public function testRegisterUserPersistsAndFlushes(): void
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed_password');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $service = new RegistrationService(
            $entityManager,
            $this->createStub(UserRepository::class),
            $passwordHasher,
            $validator,
        );

        $user = $service->registerUser('new@example.com', 'StrongPass1');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('new@example.com', $user->getEmail());
    }

    public function testRegisterUserHashesPassword(): void
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'StrongPass1')
            ->willReturn('hashed_strong_pass');

        $service = new RegistrationService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(UserRepository::class),
            $passwordHasher,
            $validator,
        );

        $user = $service->registerUser('hash@example.com', 'StrongPass1');

        $this->assertSame('hashed_strong_pass', $user->getPassword());
    }

    public function testRegisterUserErasesPlainPassword(): void
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed');

        $service = new RegistrationService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(UserRepository::class),
            $passwordHasher,
            $validator,
        );

        $user = $service->registerUser('erase@example.com', 'StrongPass1');

        $this->assertNull($user->getPlainPassword());
    }

    public function testRegisterUserThrowsValidationFailedException(): void
    {
        $violation = new ConstraintViolation(
            'Email is invalid',
            null,
            [],
            null,
            'email',
            'bad-email',
        );
        $violations = new ConstraintViolationList([$violation]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violations);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->never())
            ->method('hashPassword');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');

        $service = new RegistrationService(
            $entityManager,
            $this->createStub(UserRepository::class),
            $passwordHasher,
            $validator,
        );

        $this->expectException(ValidationFailedException::class);

        $service->registerUser('bad-email', 'StrongPass1');
    }
}
