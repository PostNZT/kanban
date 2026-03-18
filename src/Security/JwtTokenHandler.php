<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class JwtTokenHandler implements AccessTokenHandlerInterface
{
    private const ALGORITHM = 'HS256';
    private const TOKEN_TTL = 86400; // 24 hours

    public function __construct(
        private readonly string $jwtSecret,
    ) {
    }

    public function createToken(int $userId, string $email): string
    {
        $payload = [
            'sub' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + self::TOKEN_TTL,
        ];

        return JWT::encode($payload, $this->jwtSecret, self::ALGORITHM);
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $decoded = JWT::decode($accessToken, new Key($this->jwtSecret, self::ALGORITHM));
        } catch (\Exception) {
            throw new BadCredentialsException('Invalid token.');
        }

        if (!isset($decoded->email)) {
            throw new BadCredentialsException('Invalid token payload.');
        }

        return new UserBadge($decoded->email);
    }
}
