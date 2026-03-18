<?php

namespace App\Tests\Unit\Security;

use App\Security\CookieTokenExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CookieTokenExtractorTest extends TestCase
{
    private CookieTokenExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CookieTokenExtractor();
    }

    public function testExtractsTokenFromAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer my-jwt-token-from-header');

        $token = $this->extractor->extractAccessToken($request);

        $this->assertSame('my-jwt-token-from-header', $token);
    }

    public function testExtractsTokenFromCookie(): void
    {
        $request = new Request(cookies: ['jwt_token' => 'my-jwt-token-from-cookie']);

        $token = $this->extractor->extractAccessToken($request);

        $this->assertSame('my-jwt-token-from-cookie', $token);
    }

    public function testPrefersAuthorizationHeaderOverCookie(): void
    {
        $request = new Request(cookies: ['jwt_token' => 'cookie-token']);
        $request->headers->set('Authorization', 'Bearer header-token');

        $token = $this->extractor->extractAccessToken($request);

        $this->assertSame('header-token', $token);
    }

    public function testReturnsNullWhenNoTokenPresent(): void
    {
        $request = new Request();

        $token = $this->extractor->extractAccessToken($request);

        $this->assertNull($token);
    }
}
