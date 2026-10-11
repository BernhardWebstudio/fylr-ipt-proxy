<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\SessionSanitizerSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionSanitizerSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_COOKIE['MYSESSID']);
        parent::tearDown();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SessionSanitizerSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertEquals('onKernelRequest', $events[KernelEvents::REQUEST][0][0]);
        $this->assertEquals(256, $events[KernelEvents::REQUEST][0][1]);
    }

    public function testNotMainRequestDoesNothing(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
        
        $this->assertFalse($request->hasSession());
    }

    public function testNoSessionDoesNothing(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
        
        $this->assertFalse($request->hasSession());
    }

    public function testValidSessionCookieIsNotRemoved(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        
        $sessionName = 'MYSESSID';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn($sessionName);
        
        $request->setSession($session);
        $request->cookies->set($sessionName, 'abc-123,456');
        
        $_COOKIE[$sessionName] = 'abc-123,456';
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
        
        $this->assertTrue($request->cookies->has($sessionName));
        $this->assertEquals('abc-123,456', $request->cookies->get($sessionName));
        $this->assertEquals('abc-123,456', $_COOKIE[$sessionName]);
    }

    public function testInvalidSessionCookieIsRemoved(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        
        $sessionName = 'MYSESSID';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn($sessionName);
        
        $request->setSession($session);
        $request->cookies->set($sessionName, 'invalid$id{jndi:ldap}');
        
        $_COOKIE[$sessionName] = 'invalid$id{jndi:ldap}';
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
        
        $this->assertFalse($request->cookies->has($sessionName));
        $this->assertArrayNotHasKey($sessionName, $_COOKIE);
    }

    public function testTooLongSessionCookieIsRemoved(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        
        $sessionName = 'MYSESSID';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn($sessionName);
        
        $request->setSession($session);
        
        $tooLongId = str_repeat('a', 129);
        $request->cookies->set($sessionName, $tooLongId);
        $_COOKIE[$sessionName] = $tooLongId;
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
        
        $this->assertFalse($request->cookies->has($sessionName));
        $this->assertArrayNotHasKey($sessionName, $_COOKIE);
    }

    public function testDoesNotTriggerUninitializedSessionFactory(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $factoryCalled = false;
        $request->setSessionFactory(function () use (&$factoryCalled) {
            $factoryCalled = true;
            return $this->createMock(SessionInterface::class);
        });

        $request->cookies->set('PHPSESSID', 'invalid/cookie');
        $_COOKIE['PHPSESSID'] = 'invalid/cookie';

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);

        $this->assertFalse($factoryCalled, 'Sanitizer should not invoke the lazy session factory closure');
        $this->assertFalse($request->cookies->has('PHPSESSID'));
        $this->assertArrayNotHasKey('PHPSESSID', $_COOKIE);

        unset($_COOKIE['PHPSESSID']);
    }

    public function testResetsNativeSessionIdWhenInvalidCookieDetected(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->cookies->set('PHPSESSID', 'invalid$cookie');

        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            \session_id('dirty_session_id_from_worker');
        }

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);

        $this->assertSame('', \session_id());
    }
}
