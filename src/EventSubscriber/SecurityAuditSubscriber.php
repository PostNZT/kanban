<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SecurityAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUserIdentifier();
        $ip = $event->getRequest()->getClientIp();

        $this->logger->info('Successful authentication.', [
            'user' => $user,
            'ip' => $ip,
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $ip = $event->getRequest()->getClientIp();

        $this->logger->warning('Failed authentication attempt.', [
            'ip' => $ip,
        ]);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if ($exception instanceof AccessDeniedException) {
            $this->logger->warning('Access denied.', [
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
            ]);
        }

        if ($exception instanceof BadCredentialsException) {
            $this->logger->warning('Bad credentials.', [
                'path' => $request->getPathInfo(),
                'ip' => $request->getClientIp(),
            ]);
        }
    }
}
