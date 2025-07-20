<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Utility service for EasyDB operations in authenticated context
 */
class EasydbApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EasydbSessionService $sessionService,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    /**
     * Initialize EasyDB session from stored session data
     */
    public function initializeFromSession(): bool
    {
        $session = $this->requestStack->getSession();

        if (!$session) {
            return false;
        }

        $token = $session->get('easydb_token');
        $sessionContent = $session->get('easydb_session_content');

        if (!$token || !$sessionContent) {
            return false;
        }

        try {
            // Restore session state
            $reflection = new \ReflectionClass($this->sessionService);

            $tokenProperty = $reflection->getProperty('token');
            $tokenProperty->setAccessible(true);
            $tokenProperty->setValue($this->sessionService, $token);

            $contentProperty = $reflection->getProperty('sessionContent');
            $contentProperty->setAccessible(true);
            $contentProperty->setValue($this->sessionService, $sessionContent);

            // Verify session is still valid
            $this->sessionService->retrieveCurrentSession();

            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to restore EasyDB session', [
                'error' => $e->getMessage()
            ]);

            // Clear invalid session data
            $session->remove('easydb_token');
            $session->remove('easydb_session_content');

            return false;
        }
    }

    /**
     * Perform a search query against EasyDB
     */
    public function search(array $query): array
    {
        if (!$this->initializeFromSession()) {
            throw new \RuntimeException('No valid EasyDB session available');
        }

        // This would implement the actual search logic
        // For now, returning a placeholder
        return [
            'query' => $query,
            'results' => []
        ];
    }

    /**
     * Load an entity by its global object ID
     */
    public function loadEntityByGlobalObjectID(string $globalObjectID): ?array
    {
        if (!$this->initializeFromSession()) {
            throw new \RuntimeException('No valid EasyDB session available');
        }

        $response = $this->httpClient->request('POST', $this->sessionService->getUrl("search"), [
            'json' => [
                'format' => 'long',
                'search' => [
                    [
                        'fields' => ['_global_object_id'],
                        'in' => [$globalObjectID],
                        'type' => 'in',
                    ]
                ]
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content;
    }

    /**
     * Load entities for a specific pool type
     */
    public function loadEntitiesForPool(string $poolType, int $offset = 0, int $limit = 100): array
    {
        if (!$this->initializeFromSession()) {
            throw new \RuntimeException('No valid EasyDB session available');
        }

        $response = $this->httpClient->request('POST', $this->sessionService->getUrl("search"), [
            'json' => [
                'format' => 'long',
                'search' => [
                    [
                        'type' => 'object',
                        'objecttypes' => [$poolType],
                        'offset' => $offset,
                        'limit' => $limit,
                        'search' => [],
                        'sort' => [
                            [
                                'field' => '_system_object_id',
                                'order' => 'DESC',
                                '_level' => 0
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content;
    }

    /**
     * Check if user has valid EasyDB session
     */
    public function hasValidSession(): bool
    {
        return $this->initializeFromSession();
    }
}
