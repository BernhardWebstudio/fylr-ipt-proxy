<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling EasyDB API session management
 * Ported from Python session.py script
 */
class EasydbSessionService
{
    private string $baseUrl;
    private string $newSessionUrl;
    private string $authSessionUrl;
    private string $deauthSessionUrl;
    private string $searchUrl;
    private string $pluginUrl;
    private string $serverUrl;

    private ?string $token = null;
    private ?array $sessionContent = null;
    private ?array $sessionHeader = null;
    private ?string $login = null;
    private ?string $password = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $easydbServerUrl
    ) {
        $this->initializeUrls($easydbServerUrl);
    }

    private function initializeUrls(string $server): void
    {
        // Remove trailing slash and add http if not present
        $server = rtrim($server, '/');
        if (!str_starts_with($server, 'http')) {
            $server = 'http://' . $server;
        }

        $this->baseUrl = $server . '/api/v1/';
        $this->newSessionUrl = $this->baseUrl . 'session';
        $this->authSessionUrl = $this->baseUrl . 'session/authenticate';
        $this->deauthSessionUrl = $this->baseUrl . 'session/deauthenticate';
        $this->searchUrl = $this->baseUrl . 'search';
        $this->pluginUrl = $this->baseUrl . 'plugin';
        $this->serverUrl = $this->baseUrl . 'plugin/base/server/status';
    }

    /**
     * Create new session using URL directed towards database
     */
    public function startSession(): array
    {
        $this->logger->info('Starting EasyDB session');

        try {
            $response = $this->httpClient->request('GET', $this->newSessionUrl);
            $this->checkStatusCode($response);

            $content = $response->toArray();
            $this->sessionContent = $content;
            $this->sessionHeader = $response->getHeaders();
            $this->token = $this->getValue($content, 'token');

            $this->logger->info('EasyDB session started successfully', ['token' => $this->token]);

            return $content;
        } catch (\Exception $e) {
            $this->logger->error('Failed to start EasyDB session', [
                'error' => $e->getMessage(),
                'url' => $this->newSessionUrl
            ]);
            throw new \RuntimeException('Failed to start EasyDB session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve the same session using Token and plain url
     * Compare instances to prove similarity
     */
    public function retrieveCurrentSession(): array
    {
        if (!$this->token) {
            throw new \RuntimeException('No active session token available');
        }

        $payload = ['token' => $this->token];

        $this->logger->info('Retrieving current EasyDB session', $payload);

        $response = $this->httpClient->request('GET', $this->newSessionUrl, [
            'query' => $payload
        ]);

        $this->checkStatusCode($response);
        $content = $response->toArray();

        // Proof that the session is the same
        if ($this->getValue($content, 'instance') === $this->getValue($this->sessionContent, 'instance')) {
            $this->logger->info('Retrieved correct EasyDB session');
        }

        return $content;
    }

    /**
     * Authenticate Session using authenticate url
     * login and password credentials required, or email instead of login
     */
    public function authenticateSession(string $login, string $password): array
    {
        if (!$this->token) {
            throw new \RuntimeException('No active session token available');
        }

        $this->login = $login;
        $this->password = $password;

        $payload = [
            'token' => $this->token,
            'login' => $login,
            'password' => $password
        ];

        $this->logger->info('Authenticating EasyDB session', [
            'token' => $this->token,
            'login' => $login
        ]);

        $response = $this->httpClient->request('POST', $this->authSessionUrl, [
            'query' => $payload
        ]);

        $this->checkStatusCode($response);
        $content = $response->toArray();

        $this->logger->info('EasyDB session authenticated successfully');

        return $content;
    }

    /**
     * Deauthenticate session using deauthenticate url
     */
    public function deauthenticateSession(): array
    {
        if (!$this->token) {
            throw new \RuntimeException('No active session token available');
        }

        $payload = ['token' => $this->token];

        $this->logger->info('Deauthenticating EasyDB session', $payload);

        $response = $this->httpClient->request('POST', $this->deauthSessionUrl, [
            'query' => $payload
        ]);

        $this->checkStatusCode($response, false);
        $content = $response->toArray();

        // Clear session data
        $this->token = null;
        $this->sessionContent = null;
        $this->sessionHeader = null;
        $this->login = null;
        $this->password = null;

        $this->logger->info('EasyDB session deauthenticated successfully');

        return $content;
    }

    /**
     * Get URL with token parameter
     */
    public function getUrl(string $path): string
    {
        if (!$this->token) {
            throw new \RuntimeException('No active session token available');
        }

        $separator = str_contains($path, '?') ? '&' : '?';
        return $this->baseUrl . $path . $separator . 'token=' . $this->token;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->token !== null && $this->login !== null;
    }

    /**
     * Get current token
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get current login
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * Get session content
     */
    public function getSessionContent(): ?array
    {
        return $this->sessionContent;
    }

    /**
     * Helper method to get value from array
     */
    private function getValue(array $data, string $key): mixed
    {
        return $data[$key] ?? null;
    }

    /**
     * Check HTTP status code and throw exception if not 200
     */
    public function checkStatusCode(ResponseInterface $response, bool $exitOnFailure = true): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $errorMessage = sprintf('Got status code %d', $statusCode);

            try {
                $content = $response->toArray(false);
                $errorMessage .= ': ' . json_encode($content, JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                $errorMessage .= ': Could not decode response body';
            }

            $this->logger->error('EasyDB API request failed', [
                'status_code' => $statusCode,
                'response' => $errorMessage
            ]);

            if ($exitOnFailure) {
                throw new \RuntimeException($errorMessage);
            }
        }
    }
}
