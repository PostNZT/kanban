<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriberTest extends TestCase
{
    private function createResponseEvent(int $requestType): ResponseEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response();

        return new ResponseEvent($kernel, $request, $requestType, $response);
    }

    public function testGetSubscribedEventsReturnsResponseEvent(): void
    {
        $events = SecurityHeadersSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame('onKernelResponse', $events[KernelEvents::RESPONSE]);
    }

    public function testSetsSecurityHeadersOnMainRequest(): void
    {
        $subscriber = new SecurityHeadersSubscriber('dev');
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
        $this->assertSame('0', $headers->get('X-XSS-Protection'));
        $this->assertSame('strict-origin-when-cross-origin', $headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $headers->get('Permissions-Policy'));
    }

    public function testDoesNotSetHeadersOnSubRequest(): void
    {
        $subscriber = new SecurityHeadersSubscriber('prod');
        $event = $this->createResponseEvent(HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertNull($headers->get('X-Content-Type-Options'));
        $this->assertNull($headers->get('X-Frame-Options'));
        $this->assertNull($headers->get('X-XSS-Protection'));
        $this->assertNull($headers->get('Referrer-Policy'));
        $this->assertNull($headers->get('Permissions-Policy'));
    }

    public function testSetsHstsInProdEnvironment(): void
    {
        $subscriber = new SecurityHeadersSubscriber('prod');
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelResponse($event);

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $event->getResponse()->headers->get('Strict-Transport-Security'),
        );
    }

    public function testSetsCspInProdEnvironment(): void
    {
        $subscriber = new SecurityHeadersSubscriber('prod');
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelResponse($event);

        $this->assertSame(
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'",
            $event->getResponse()->headers->get('Content-Security-Policy'),
        );
    }

    public function testDoesNotSetHstsInDevEnvironment(): void
    {
        $subscriber = new SecurityHeadersSubscriber('dev');
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelResponse($event);

        $this->assertNull($event->getResponse()->headers->get('Strict-Transport-Security'));
    }

    public function testDoesNotSetCspInDevEnvironment(): void
    {
        $subscriber = new SecurityHeadersSubscriber('dev');
        $event = $this->createResponseEvent(HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelResponse($event);

        $this->assertNull($event->getResponse()->headers->get('Content-Security-Policy'));
    }
}
