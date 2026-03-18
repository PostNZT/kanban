<?php

namespace App\Controller;

use App\Service\AuthenticationService;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly AuthenticationService $authenticationService,
        private readonly RateLimiterFactory $loginLimiter,
        private readonly RateLimiterFactory $registrationLimiter,
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $limiter = $this->registrationLimiter->create($request->getClientIp());
        if (false === $limiter->consume()->isAccepted()) {
            return $this->json(['error' => 'Too many registration attempts. Please try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->registrationService->isEmailTaken($data['email'])) {
            return $this->json(['error' => 'Email already in use.'], Response::HTTP_CONFLICT);
        }

        try {
            $user = $this->registrationService->registerUser($data['email'], $data['password']);
        } catch (ValidationFailedException $exception) {
            $messages = [];
            foreach ($exception->getViolations() as $violation) {
                $messages[] = $violation->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $limiter = $this->loginLimiter->create($request->getClientIp());
        if (false === $limiter->consume()->isAccepted()) {
            return $this->json(['error' => 'Too many login attempts. Please try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->authenticationService->authenticate($data['email'], $data['password']);

        if (!$result) {
            return $this->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        $response = $this->json([
            'user' => $result['user'],
        ]);

        $response->headers->setCookie(
            \Symfony\Component\HttpFoundation\Cookie::create('jwt_token')
                ->withValue($result['token'])
                ->withExpires(time() + 1800)
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSameSite('strict')
                ->withSecure(true)
        );

        return $response;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->json(['message' => 'Logged out.']);

        $response->headers->clearCookie('jwt_token', '/', null, true, true, 'strict');

        return $response;
    }
}
