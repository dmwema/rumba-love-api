<?php

namespace App\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LiveAccessTokenService
{
    private const LIVE_ACCESS_ROLE = 'ROLE_LIVE_ACCESS';

    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {}

    public function generateLiveAccessToken(int $userId, string $code): string
    {
        $payload = [
            'user_id' => $userId,
            'code' => $code,
            'roles' => [self::LIVE_ACCESS_ROLE],
            'exp' => time() + 300, // 5 minutes
            'iat' => time(),
            'type' => 'live_access'
        ];

        // Créer un token personnalisé avec payload spécifique
        return $this->jwtManager->createFromPayload($this->jwtManager->create(new \Symfony\Component\Security\Core\User\User('live_user', '')), $payload);
    }

    public function validateLiveAccessToken(string $token): array
    {
        try {
            $payload = $this->jwtManager->parse($token);

            // Vérifier le type de token
            if (!isset($payload['type']) || $payload['type'] !== 'live_access') {
                throw new \InvalidArgumentException('Invalid token type');
            }

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new \InvalidArgumentException('Token expired');
            }

            // Vérifier le rôle
            if (!isset($payload['roles']) || !in_array(self::LIVE_ACCESS_ROLE, $payload['roles'])) {
                throw new \InvalidArgumentException('Invalid token roles');
            }

            return $payload;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid live access token: ' . $e->getMessage());
        }
    }
}