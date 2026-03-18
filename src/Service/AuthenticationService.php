<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Security\JwtTokenHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JwtTokenHandler $jwtTokenHandler,
    ) {
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return [
            'token' => $this->jwtTokenHandler->createToken($user->getId(), $user->getEmail()),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
        ];
    }
}
