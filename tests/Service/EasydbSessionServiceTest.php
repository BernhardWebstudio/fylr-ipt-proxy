<?php

namespace App\Tests\Service;

use App\Service\EasydbSessionService;
use App\Service\EasydbApiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EasydbSessionServiceTest extends TestCase
{
    public function testResetSessionServiceClearsState(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new EasydbSessionService($httpClient, $logger, 'https://example.com', 'client_id', 'client_secret');

        // Set state via reflection
        $reflection = new \ReflectionClass($service);
        
        $accessTokenProperty = $reflection->getProperty('accessToken');
        $accessTokenProperty->setValue($service, 'test-token');

        $isFylrProperty = $reflection->getProperty('isFylr');
        $isFylrProperty->setValue($service, true);

        $this->assertEquals('test-token', $service->getAuthToken());
        $this->assertTrue($service->isFylr());

        $service->reset();

        $this->assertNull($service->getAuthToken());
        $this->assertFalse($service->isFylr());
    }

    public function testResetApiServiceClearsState(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $sessionService = $this->createMock(EasydbSessionService::class);

        $sessionService->expects($this->once())
            ->method('reset');

        $apiService = new EasydbApiService($httpClient, $sessionService, $requestStack, $logger);

        // Set state via reflection
        $reflection = new \ReflectionClass($apiService);
        $isInitializedProperty = $reflection->getProperty('isInitialized');
        $isInitializedProperty->setValue($apiService, true);

        $this->assertTrue($apiService->isInitialized());

        $apiService->reset();

        $this->assertFalse($apiService->isInitialized());
    }
}
