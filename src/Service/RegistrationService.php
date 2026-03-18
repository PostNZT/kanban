<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function isEmailTaken(string $email): bool
    {
        return $this->userRepository->findOneBy(['email' => $email]) !== null;
    }

    public function registerUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPlainPassword($password);

        $errors = $this->validator->validate($user, null, ['Default', 'registration']);
        if (count($errors) > 0) {
            throw new ValidationFailedException($user, $errors);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->eraseCredentials();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
