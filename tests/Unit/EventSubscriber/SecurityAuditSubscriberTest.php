<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityAuditSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SecurityAuditSubscriberTest extends TestCase
{
    private LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger;
    private SecurityAuditSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new SecurityAuditSubscriber($this->logger);
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create('/api/test');

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testGetSubscribedEventsReturnsCorrectEvents(): void
    {
        $this->logger->expects($this->never())->method($this->anything());

        $events = SecurityAuditSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(LoginSuccessEvent::class, $events);
        $this->assertSame('onLoginSuccess', $events[LoginSuccessEvent::class]);

        $this->assertArrayHasKey(LoginFailureEvent::class, $events);
        $this->assertSame('onLoginFailure', $events[LoginFailureEvent::class]);

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertSame('onKernelException', $events[KernelEvents::EXCEPTION]);
    }

    public function testOnKernelExceptionLogsAccessDenied(): void
    {
        $event = $this->createExceptionEvent(new AccessDeniedException('Forbidden'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Access denied.', $this->callback(function (array $context): bool {
                return $context['path'] === '/api/test'
                    && $context['method'] === 'GET'
                    && array_key_exists('ip', $context);
            }));

        $this->subscriber->onKernelException($event);
    }

    public function testOnKernelExceptionLogsBadCredentials(): void
    {
        $event = $this->createExceptionEvent(new BadCredentialsException('Invalid credentials'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Bad credentials.', $this->callback(function (array $context): bool {
                return $context['path'] === '/api/test'
                    && array_key_exists('ip', $context);
            }));

        $this->subscriber->onKernelException($event);
    }

    public function testOnKernelExceptionIgnoresOtherExceptions(): void
    {
        $event = $this->createExceptionEvent(new \RuntimeException('Something went wrong'));

        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('error');

        $this->subscriber->onKernelException($event);
    }
}
