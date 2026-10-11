<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SessionSanitizerSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<string, mixed> $sessionOptions
     */
    public function __construct(
        #[Autowire('%session.storage.options%')]
        private readonly array $sessionOptions = [],
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only run on the main request (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $sessionNames = [
            $this->sessionOptions['name'] ?? null,
            \ini_get('session.name') ?: null,
            \session_name() ?: null,
            'PHPSESSID',
        ];

        // Only access session name if already initialized (skip uninitialized factory closure)
        if ($request->hasSession(true)) {
            $sessionNames[] = $request->getSession()->getName();
        }

        $sessionNames = array_unique(array_filter($sessionNames));

        foreach ($sessionNames as $sessionName) {
            // If the session cookie is present in the request
            if (($sessionId = $request->cookies->get($sessionName))) {
                // Validate session ID. Valid characters in PHP: A-Z, a-z, 0-9, "-", and ","
                // We also check length to prevent extremely long malicious inputs (e.g. > 128 characters)
                if (
                    !\is_string($sessionId)
                    || $sessionId === ''
                    || !preg_match('/^[a-zA-Z0-9,-]+$/', $sessionId)
                    || \strlen($sessionId) > 128
                ) {
                    // Remove from Symfony request cookies
                    $request->cookies->remove($sessionName);

                    // Remove from superglobal to prevent native php session_start() from picking it up
                    if (isset($_COOKIE[$sessionName])) {
                        unset($_COOKIE[$sessionName]);
                    }

                    // Reset native session ID in worker runtimes (e.g. FrankenPHP) if not active
                    if (\session_status() !== \PHP_SESSION_ACTIVE && \session_id() !== '') {
                        \session_id('');
                    }
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority needs to be higher than SessionListener (128) so invalid cookies
            // are stripped before Symfony registers and executes the session factory.
            KernelEvents::REQUEST => [['onKernelRequest', 256]],
        ];
    }
}

