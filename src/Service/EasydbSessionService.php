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
    private string $oauthTokenUrl;

    private ?string $token = null;
    private ?string $accessToken = null;
    private ?array $sessionContent = null;
    private ?array $sessionHeader = null;
    private ?string $login = null;
    private ?string $password = null;
    private bool $isFylr = false;
    private ?string $clientId = null;
    private ?string $clientSecret = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $easydbServerUrl,
        ?string $clientId = null,
        ?string $clientSecret = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
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
        $this->oauthTokenUrl = $server . '/api/oauth2/token';
    }

    /**
     * Create new session using URL directed towards database
     */
    public function startSession(): array
    {
        $this->logger->info('Starting EasyDB session');

        try {
            $response = $this->httpClient->request('GET', $this->newSessionUrl);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 400) {
                $this->logger->info('Detected Fylr instance (400 on session endpoint)');
                $this->isFylr = true;
                return []; // No session content for Fylr
            }

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
     * Retrieve the same session using Token and plain url (for EasyDB only)
     * Compare instances to prove similarity
     */
    public function retrieveCurrentSession(): array
    {
        if ($this->isFylr) {
            throw new \RuntimeException('retrieveCurrentSession is not supported for Fylr');
        }

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
     * Authenticate Session using authenticate url or OAuth2 for Fylr
     * login and password credentials required, or email instead of login
     */
    public function authenticateSession(string $login, string $password): array
    {
        $this->login = $login;
        $this->password = $password;

        if ($this->isFylr) {
            return $this->authenticateFylr($login, $password);
        } else {
            return $this->authenticateEasydb($login, $password);
        }
    }

    private function authenticateEasydb(string $login, string $password): array
    {
        if (!$this->token) {
            throw new \RuntimeException('No active session token available');
        }

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

    private function authenticateFylr(string $login, string $password): array
    {
        $payload = [
            'grant_type' => 'password',
            'username' => $login,
            'password' => $password,
        ];

        if ($this->clientId) {
            $payload['client_id'] = $this->clientId;
        }
        if ($this->clientSecret) {
            $payload['client_secret'] = $this->clientSecret;
        }

        $this->logger->info('Authenticating Fylr via OAuth2', [
            'login' => $login,
            'client_id' => $this->clientId
        ]);

        $response = $this->httpClient->request('POST', $this->oauthTokenUrl, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => http_build_query($payload)
        ]);

        $this->checkStatusCode($response);
        $content = $response->toArray();

        $this->accessToken = $this->getValue($content, 'access_token');

        $this->logger->info('Fylr authenticated successfully');

        return $content;
    }

    /**
     * Deauthenticate session using deauthenticate url or clear for Fylr
     */
    public function deauthenticateSession(): array
    {
        if ($this->isFylr) {
            // For Fylr, no deauth endpoint mentioned, just clear
            $this->logger->info('Deauthenticating Fylr session (clearing tokens)');
            $this->clearSession();
            return [];
        }

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

        $this->clearSession();

        $this->logger->info('EasyDB session deauthenticated successfully');

        return $content;
    }

    private function clearSession(): void
    {
        $this->token = null;
        $this->accessToken = null;
        $this->sessionContent = null;
        $this->sessionHeader = null;
        $this->login = null;
        $this->password = null;
    }

    /**
     * Get URL with token or access_token parameter
     */
    public function getUrl(string $path): string
    {
        $token = $this->isFylr ? $this->accessToken : $this->token;

        if (!$token) {
            throw new \RuntimeException('No active token available');
        }

        $separator = str_contains($path, '?') ? '&' : '?';
        $param = $this->isFylr ? 'access_token' : 'token';
        return $this->baseUrl . $path . $separator . $param . '=' . $token;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return ($this->token !== null || $this->accessToken !== null) && $this->login !== null;
    }

    /**
     * Get current token (for EasyDB) or access_token (for Fylr)
     */
    public function getAuthToken(): ?string
    {
        return $this->isFylr ? $this->accessToken : $this->token;
    }

    /**
     * Get current login
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * Check if this is a Fylr instance
     */
    public function isFylr(): bool
    {
        return $this->isFylr;
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
