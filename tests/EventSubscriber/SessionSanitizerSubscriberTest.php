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
        $this->assertEquals(100, $events[KernelEvents::REQUEST][0][1]);
    }

    public function testNotMainRequestDoesNothing(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        
        // This should not call hasSession on the request because it's a sub-request
        $request->expects($this->never())->method('hasSession');
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
    }

    public function testNoSessionDoesNothing(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->method('hasSession')->willReturn(false);
        
        // This should not call getSession because hasSession is false
        $request->expects($this->never())->method('getSession');
        
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $subscriber = new SessionSanitizerSubscriber();
        $subscriber->onKernelRequest($event);
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
}
