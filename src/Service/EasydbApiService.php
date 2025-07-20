<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

/**
 * Utility service for EasyDB operations in authenticated context
 */
class EasydbApiService
{
    public function __construct(
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
     * Get EasyDB server status
     */
    public function getServerStatus(): array
    {
        if (!$this->initializeFromSession()) {
            throw new \RuntimeException('No valid EasyDB session available');
        }

        // Implementation would use the session service to get server status
        return [
            'status' => 'connected',
            'token' => $this->sessionService->getToken()
        ];
    }

    /**
     * Check if user has valid EasyDB session
     */
    public function hasValidSession(): bool
    {
        return $this->initializeFromSession();
    }
}
