<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Try to get locale from session
        if ($request->hasPreviousSession()) {
            $session = $request->getSession();
            $sessionName = $session->getName();
            $sessionId = $request->cookies->get($sessionName);

            // Guard against starting session with invalid/malicious session ID from crawlers
            if (
                \is_string($sessionId)
                && $sessionId !== ''
                && \preg_match('/^[a-zA-Z0-9,-]+$/', $sessionId)
                && \strlen($sessionId) <= 128
            ) {
                $locale = $session->get('_locale');

                if ($locale) {
                    $request->setLocale($locale);
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
