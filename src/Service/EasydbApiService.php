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
    private bool $isInitialized = false;

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
        // Skip if already initialized
        if ($this->isInitialized) {
            return true;
        }

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
            $this->restoreSessionState($token, $sessionContent);
            $this->isInitialized = true;
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
     * Initialize EasyDB session from explicit credentials (for use in async contexts like message handlers)
     */
    public function initializeFromCredentials(?string $token, ?array $sessionContent): bool
    {
        // Skip if already initialized
        if ($this->isInitialized) {
            return true;
        }

        if (!$token || !$sessionContent) {
            return false;
        }

        try {
            $this->restoreSessionState($token, $sessionContent);
            $this->isInitialized = true;
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to restore EasyDB session from credentials', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Initialize EasyDB session using login/password credentials.
     * Suitable for webhook or background contexts where HTTP session is unavailable.
     */
    public function initializeFromLoginPassword(?string $login, ?string $password): bool
    {
        // Skip if already initialized
        if ($this->isInitialized) {
            return true;
        }

        if (!$login || !$password) {
            return false;
        }

        try {
            // Start a fresh session and authenticate
            $this->sessionService->startSession();
            $this->sessionService->authenticateSession($login, $password);

            // Mark initialized; token and session are held by EasydbSessionService
            $this->isInitialized = true;
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to initialize EasyDB session via login/password', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Ensure the service is initialized, attempting to initialize from HTTP session if needed
     */
    private function ensureInitialized(): void
    {
        if ($this->isInitialized) {
            return;
        }

        // Try to initialize from HTTP session (for web requests)
        if ($this->initializeFromSession()) {
            return;
        }

        // If we couldn't initialize, throw exception
        throw new \RuntimeException('No valid EasyDB session available. The service must be initialized with credentials.');
    }

    /**
     * Restore session state to the EasydbSessionService
     */
    private function restoreSessionState(string $token, array $sessionContent): void
    {
        $reflection = new \ReflectionClass($this->sessionService);

        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($this->sessionService, $token);

        $contentProperty = $reflection->getProperty('sessionContent');
        $contentProperty->setAccessible(true);
        $contentProperty->setValue($this->sessionService, $sessionContent);

        // Verify session is still valid
        $this->sessionService->retrieveCurrentSession();
    }

    /**
     * Reset the initialization state (useful for testing or when switching contexts)
     */
    public function reset(): void
    {
        $this->isInitialized = false;
    }

    /**
     * Perform a search query against EasyDB
     */
    public function search(array $query): array
    {
        $this->ensureInitialized();

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
        $this->ensureInitialized();

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
        assert(isset($content['objects']));
        if (count($content['objects']) === 0) {
            return null;
        }
        assert(count($content['objects']) === 1);

        return $content['objects'][0];
    }

    /**
     * Load an entity by its global object ID
     */
    public function loadEntityByUUIDAndSystemObjectID(string $uuid, int $systemObjectID): ?array
    {
        $this->ensureInitialized();

        $response = $this->httpClient->request('POST', $this->sessionService->getUrl("search"), [
            'json' => [
                'format' => 'long',
                'search' => [
                    [
                        "search" => [
                            [
                                'fields' => ['_system_object_id'],
                                'in' => [$systemObjectID],
                                'type' => 'in',
                                'bool' => 'must',
                            ],
                            [
                                'fields' => ['_uuid'],
                                'in' => [$uuid],
                                'type' => 'in',
                                'bool' => 'must',
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        assert(isset($content['objects']));
        if (count($content['objects']) === 0) {
            return null;
        }
        assert(count($content['objects']) === 1);

        return $content['objects'][0];
    }

    /**
     * Load entities for a specific pool type
     */
    public function loadEntitiesForPool(string $poolType, int $offset = 0, int $limit = 100): array
    {
        $this->ensureInitialized();

        $response = $this->httpClient->request('POST', $this->sessionService->getUrl("search"), [
            'json' => [
                'format' => 'long',
                'search' => [
                    [
                        'search' => [[
                            'type' => 'in',
                            'in' => [$poolType],
                            'fields' => ['_objecttype']
                        ]],
                        'type' => 'complex',
                        // 'search' => [],
                        'sort' => [
                            [
                                'field' => '_system_object_id',
                                'order' => 'DESC',
                                '_level' => 0
                            ]
                        ]
                    ]
                ],
                'offset' => $offset,
                'limit' => $limit,
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content['objects'] ?? [];
    }

    /**
     * Fetch all available tags from EasyDB
     */
    public function fetchTags(): array
    {
        $this->ensureInitialized();

        $response = $this->httpClient->request('GET', $this->sessionService->getUrl("tags"), [
            'headers' => [
                'accept' => 'application/json',
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content;
    }

    public function fetchObjectTypes(): array
    {
        $this->ensureInitialized();

        $response = $this->httpClient->request('GET', $this->sessionService->getUrl("objecttype?format=short"), [
            'headers' => [
                'accept' => 'application/json',
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content;
    }

    /**
     * Perform a fulltext search for entities
     */
    public function searchFulltext(string $searchTerm, string|array $objectType, int $offset = 0, int $limit = 100): array
    {
        $this->ensureInitialized();

        $response = $this->httpClient->request('POST', $this->sessionService->getUrl("search"), [
            'json' => [
                'objecttypes' => is_array($objectType) ? $objectType : [$objectType],
                'format' => 'long',
                'search' => [
                    [
                        'search' => [
                            [
                                'type' => 'match',
                                'mode' => 'fulltext',
                                'bool' => 'must',
                                'string' => $searchTerm,
                            ]
                        ],
                        'type' => 'complex'
                    ]
                ],
                'offset' => $offset,
                'limit' => $limit,
            ]
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content['objects'] ?? [];
    }

    /**
     * Search entities by tag ID and optionally by object type
     */
    public function searchByTag(?int $tagId = null, ?string $objectType = null, int $offset = 0, int $limit = 100): array
    {
        $this->ensureInitialized();

        $searchCriteria = [
            'type' => 'in',
            'bool' => 'must',
            'fields' => ['_tags._id'],
            'in' => [$tagId],
        ];

        $searchQuery = [
            'type' => 'complex',
            'search' => $tagId ? [$searchCriteria] : [],
            'bool' => 'must',
        ];

        // Add object type filter if specified
        if ($objectType) {
            $searchQuery['search'][] = [
                'type' => 'in',
                'bool' => 'must',
                'fields' => ['_objecttype'],
                'in' => [$objectType],
            ];
        }

        $requestBody = [
            'offset' => $offset,
            'limit' => $limit,
            'generate_rights' => false,
            'search' => [
                [
                    'type' => 'complex',
                    '__filter' => 'SearchInput',
                    'search' => [$searchQuery],
                ]
            ],
            'format' => 'standard',
            'sort' => [
                [
                    'field' => '_system_object_id',
                    'order' => 'DESC',
                    '_level' => 0
                ]
            ]
        ];

        if ($objectType) {
            $requestBody['objecttypes'] = [$objectType];
        }

        $response = $this->httpClient->request('POST', $this->sessionService->getUrl("search"), [
            'json' => $requestBody
        ]);

        $this->sessionService->checkStatusCode($response);
        $content = $response->toArray();
        return $content['objects'] ?? [];
    }

    /**
     * Search entities by multiple criteria (global object ID, tag, object type)
     */
    public function searchEntities(?string $globalObjectID = null, ?int $tagId = null, ?string $objectType = null, int $offset = 0, int $limit = 100): array
    {
        $this->ensureInitialized();

        // If global object ID is provided, use existing method
        if ($globalObjectID) {
            return [
                'objects' => [
                    $this->loadEntityByGlobalObjectID($globalObjectID)
                ]
            ];
        }

        // If tag ID is provided, use tag search
        if ($tagId) {
            return $this->searchByTag($tagId, $objectType, $offset, $limit);
        }

        // If only object type is provided, use existing pool method
        if ($objectType) {
            return $this->loadEntitiesForPool($objectType, $offset, $limit);
        }

        // If no criteria provided, return empty array
        return [];
    }

    /**
     * Check if user has valid EasyDB session
     */
    public function hasValidSession(): bool
    {
        return $this->isInitialized || $this->initializeFromSession();
    }

    /**
     * Check if the service is already initialized
     */
    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }
}
