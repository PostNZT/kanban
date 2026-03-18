<?php

namespace App\Tests\Unit\Security;

use App\Security\JwtTokenHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class JwtTokenHandlerTest extends TestCase
{
    private const SECRET = 'test-secret-key-for-unit-tests-32b!';

    private JwtTokenHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new JwtTokenHandler(self::SECRET);
    }

    public function testCreateTokenReturnsValidJwtString(): void
    {
        $token = $this->handler->createToken(1, 'user@example.com');

        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT token must have exactly 3 parts separated by dots');
        foreach ($parts as $i => $part) {
            $this->assertNotEmpty($part, "JWT part $i must not be empty");
        }
    }

    public function testCreateTokenContainsCorrectPayload(): void
    {
        $before = time();
        $token = $this->handler->createToken(42, 'alice@example.com');
        $after = time();

        $decoded = JWT::decode($token, new Key(self::SECRET, 'HS256'));

        $this->assertSame(42, $decoded->sub);
        $this->assertSame('alice@example.com', $decoded->email);
        $this->assertGreaterThanOrEqual($before, $decoded->iat);
        $this->assertLessThanOrEqual($after, $decoded->iat);
        $this->assertGreaterThanOrEqual($before + 1800, $decoded->exp);
        $this->assertLessThanOrEqual($after + 1800, $decoded->exp);
    }

    public function testGetUserBadgeFromValidToken(): void
    {
        $token = $this->handler->createToken(1, 'bob@example.com');

        $badge = $this->handler->getUserBadgeFrom($token);

        $this->assertSame('bob@example.com', $badge->getUserIdentifier());
    }

    public function testGetUserBadgeFromInvalidToken(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Invalid token.');

        $this->handler->getUserBadgeFrom('this.is.not-a-valid-jwt');
    }

    public function testGetUserBadgeFromExpiredToken(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Invalid token.');

        $payload = [
            'sub' => 1,
            'email' => 'expired@example.com',
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ];
        $expiredToken = JWT::encode($payload, self::SECRET, 'HS256');

        $this->handler->getUserBadgeFrom($expiredToken);
    }

    public function testGetUserBadgeFromTokenWithWrongSecret(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Invalid token.');

        $payload = [
            'sub' => 1,
            'email' => 'wrong@example.com',
            'iat' => time(),
            'exp' => time() + 1800,
        ];
        $tokenWithWrongSecret = JWT::encode($payload, 'completely-different-secret-key-32b!', 'HS256');

        $this->handler->getUserBadgeFrom($tokenWithWrongSecret);
    }

    public function testTokenExpiresIn30Minutes(): void
    {
        $token = $this->handler->createToken(1, 'ttl@example.com');

        $decoded = JWT::decode($token, new Key(self::SECRET, 'HS256'));

        $this->assertSame(1800, $decoded->exp - $decoded->iat);
    }
}
