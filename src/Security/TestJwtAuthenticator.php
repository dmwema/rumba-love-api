<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TestJwtAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get('Authorization');
        $supports = $authHeader && str_starts_with($authHeader, 'Bearer ');
        if ($supports) {
            error_log('TestJwtAuthenticator: Supports request for ' . $request->getPathInfo());
        }
        return $supports;
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('No Bearer token found');
        }

        $token = substr($authHeader, 7); // Remove "Bearer "
        error_log('JWT Token received: ' . substr($token, 0, 50) . '...');

        try {
            // Décoder le token JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new AuthenticationException('Invalid JWT format');
            }

            $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new AuthenticationException('Token expired');
            }

            // Vérifier la signature avec notre clé de test
            $secret = 'test_secret_key_for_development_only';
            $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret);
            $providedSignature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));

            if (!hash_equals($expectedSignature, $providedSignature)) {
                throw new AuthenticationException('Invalid token signature');
            }

            // Créer un user badge basé sur le payload
            $username = $payload['username'] ?? 'test_user';
            $roles = $payload['roles'] ?? ['ROLE_ADMIN'];

            return new SelfValidatingPassport(
                new UserBadge($username, function($username) use ($roles) {
                    return new class($username, $roles) implements \Symfony\Component\Security\Core\User\UserInterface {
                        public function __construct(private string $username, private array $roles) {}

                        public function getUserIdentifier(): string {
                            return $this->username;
                        }

                        public function getRoles(): array {
                            return $this->roles;
                        }

                        public function eraseCredentials(): void {}
                    };
                })
            );

        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Continue to the next authenticator or controller
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        error_log('JWT Authentication failed: ' . $exception->getMessage());
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}