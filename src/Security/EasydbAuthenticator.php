<?php

namespace App\Security;

use App\Entity\User;
use App\Service\EasydbSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Custom authenticator for EasyDB authentication
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class EasydbAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EasydbSessionService $easydbSession,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        // Support authentication for login forms and API token headers
        return $request->headers->has('X-AUTH-TOKEN') ||
            ($request->getPathInfo() === '/login' && $request->isMethod('POST')) ||
            $request->request->has('_username');
    }

    public function authenticate(Request $request): Passport
    {
        // Try to get API token from header first
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        if ($apiToken) {
            // Handle token-based authentication
            return $this->authenticateWithToken($apiToken);
        }

        // Handle form-based authentication
        $username = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');

        if (!$username || !$password) {
            throw new CustomUserMessageAuthenticationException('Username and password are required');
        }

        return $this->authenticateWithCredentials($username, $password);
    }

    private function authenticateWithToken(string $token): Passport
    {
        // For token authentication, validate the token with EasyDB
        try {
            // If we have a stored session with this token, retrieve it
            // This is a simplified approach - in production you might want to store token mappings
            $userIdentifier = $this->validateTokenWithEasydb($token);

            return new Passport(
                new UserBadge($userIdentifier, function ($userIdentifier) {
                    return $this->loadOrCreateUser($userIdentifier);
                }),
                new PasswordCredentials('') // Empty password for token auth
            );
        } catch (\Exception $e) {
            $this->logger->error('Token authentication failed', ['error' => $e->getMessage()]);
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }
    }

    private function authenticateWithCredentials(string $username, string $password): Passport
    {
        try {
            // Start EasyDB session
            $this->easydbSession->startSession();

            // Authenticate with EasyDB
            $sessionData = $this->easydbSession->authenticateSession($username, $password);

            $this->logger->info('EasyDB authentication successful', ['username' => $username]);

            return new Passport(
                new UserBadge($username, function ($userIdentifier) use ($sessionData) {
                    return $this->loadOrCreateUser($userIdentifier, $sessionData);
                }),
                new PasswordCredentials($password)
            );
        } catch (\Exception $e) {
            $this->logger->error('EasyDB authentication failed', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            throw new CustomUserMessageAuthenticationException('Authentication failed: ' . $e->getMessage());
        }
    }

    private function validateTokenWithEasydb(string $token): string
    {
        // This is a simplified token validation
        // In a real implementation, you might store the mapping between tokens and users
        // or validate the token directly with EasyDB

        // For now, we'll assume the token format contains user info or can be decoded
        // This would need to be implemented based on your specific EasyDB token format
        throw new \LogicException('Token validation not yet implemented');
    }

    private function loadOrCreateUser(string $userIdentifier, ?array $sessionData = null): User
    {
        // Try to find existing user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $userIdentifier]);

        if (!$user) {
            // Create new user if not exists
            $user = new User();
            $user->setUsername($userIdentifier);
            $user->setRoles(['ROLE_USER']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('Created new user from EasyDB authentication', ['username' => $userIdentifier]);
        }

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Store EasyDB session token in the user session for later use
        $session = $request->getSession();
        if ($this->easydbSession->getToken()) {
            $session->set('easydb_token', $this->easydbSession->getToken());
            $session->set('easydb_session_content', $this->easydbSession->getSessionContent());
        }

        $this->logger->info('Authentication successful', [
            'user' => $token->getUserIdentifier(),
            'firewall' => $firewallName
        ]);

        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Clean up any partial EasyDB session
        try {
            if ($this->easydbSession->getToken()) {
                $this->easydbSession->deauthenticateSession();
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to clean up EasyDB session after authentication failure', [
                'error' => $e->getMessage()
            ]);
        }

        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            'error' => 'Authentication failed'
        ];

        $this->logger->warning('Authentication failed', [
            'message' => $exception->getMessage(),
            'request_path' => $request->getPathInfo()
        ]);

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    // public function start(Request $request, ?AuthenticationException $authException = null): Response
    // {
    //     /*
    //      * If you would like this class to control what happens when an anonymous user accesses a
    //      * protected page (e.g. redirect to /login), uncomment this method and make this class
    //      * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //      *
    //      * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //      */
    // }
}
