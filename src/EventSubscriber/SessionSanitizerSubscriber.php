<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SessionSanitizerSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only run on the main request (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Check if session exists in the request
        if ($request->hasSession()) {
            $session = $request->getSession();
            $sessionName = $session->getName();

            // If the session cookie is present in the request
            if ($request->cookies->has($sessionName)) {
                $sessionId = $request->cookies->get($sessionName);

                // Validate session ID. Valid characters in PHP: A-Z, a-z, 0-9, "-", and ","
                // We also check length to prevent extremely long malicious inputs (e.g. > 128 characters)
                if (
                    !is_string($sessionId)
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
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority needs to be lower than SessionListener (128) but higher than RouterListener (32)
            KernelEvents::REQUEST => [['onKernelRequest', 100]],
        ];
    }
}
