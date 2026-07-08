<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = LocaleSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertEquals('onKernelRequest', $events[KernelEvents::REQUEST][0][0]);
        $this->assertEquals(20, $events[KernelEvents::REQUEST][0][1]);
    }

    public function testNoPreviousSessionDoesNothing(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new LocaleSubscriber();
        $subscriber->onKernelRequest($event);

        $this->assertEquals('en', $request->getLocale());
    }

    public function testValidSessionCookieSetsLocale(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $sessionName = 'MYSESSID';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn($sessionName);
        $session->expects($this->once())
            ->method('get')
            ->with('_locale')
            ->willReturn('de');

        $request->setSession($session);
        $request->cookies->set($sessionName, 'abc-123,456');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new LocaleSubscriber();
        $subscriber->onKernelRequest($event);

        $this->assertEquals('de', $request->getLocale());
    }

    public function testInvalidSessionCookieDoesNotStartSession(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $sessionName = 'MYSESSID';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn($sessionName);
        $session->expects($this->never())
            ->method('get');

        $request->setSession($session);
        $request->cookies->set($sessionName, 'invalid$id{jndi:ldap}');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new LocaleSubscriber();
        $subscriber->onKernelRequest($event);

        $this->assertEquals('en', $request->getLocale());
    }

    public function testTooLongSessionCookieDoesNotStartSession(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $sessionName = 'MYSESSID';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn($sessionName);
        $session->expects($this->never())
            ->method('get');

        $request->setSession($session);

        $tooLongId = str_repeat('a', 129);
        $request->cookies->set($sessionName, $tooLongId);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new LocaleSubscriber();
        $subscriber->onKernelRequest($event);

        $this->assertEquals('en', $request->getLocale());
    }
}
