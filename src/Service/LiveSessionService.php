<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LiveSessionService
{
    private const SESSION_PREFIX = 'live_session_';
    private const SESSION_DURATION = 3600; // 1 heure en secondes

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Génère un token de session unique pour un utilisateur
     */
    public function generateSessionToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $sessionKey = self::SESSION_PREFIX . $token;

        // Stocker en session (on pourrait utiliser Redis en production)
        // Pour l'instant, on utilise un système simple basé sur un fichier
        $sessionData = [
            'user_id' => $user->getId(),
            'created_at' => time(),
            'expires_at' => time() + self::SESSION_DURATION
        ];

        // En production, utiliser Redis ou une vraie session
        // Pour l'instant, on stocke dans un fichier temporaire
        $this->storeSession($sessionKey, $sessionData);

        return $token;
    }

    /**
     * Valide un token de session
     */
    public function validateSessionToken(string $token): ?User
    {
        $sessionKey = self::SESSION_PREFIX . $token;
        $sessionData = $this->retrieveSession($sessionKey);

        if (!$sessionData) {
            return null;
        }

        // Vérifier l'expiration
        if ($sessionData['expires_at'] < time()) {
            $this->removeSession($sessionKey);
            return null;
        }

        // Récupérer l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->find($sessionData['user_id']);

        return $user;
    }

    /**
     * Invalide un token de session
     */
    public function invalidateSessionToken(string $token): void
    {
        $sessionKey = self::SESSION_PREFIX . $token;
        $this->removeSession($sessionKey);
    }

    /**
     * Stocke les données de session (implémentation simple avec fichiers)
     */
    private function storeSession(string $key, array $data): void
    {
        $filename = sys_get_temp_dir() . '/live_sessions/' . $key . '.json';

        // Créer le répertoire s'il n'existe pas
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filename, json_encode($data));
    }

    /**
     * Récupère les données de session
     */
    private function retrieveSession(string $key): ?array
    {
        $filename = sys_get_temp_dir() . '/live_sessions/' . $key . '.json';

        if (!file_exists($filename)) {
            return null;
        }

        $data = json_decode(file_get_contents($filename), true);
        return $data ?: null;
    }

    /**
     * Supprime une session
     */
    private function removeSession(string $key): void
    {
        $filename = sys_get_temp_dir() . '/live_sessions/' . $key . '.json';

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Nettoie les sessions expirées (à appeler périodiquement)
     */
    public function cleanupExpiredSessions(): void
    {
        $sessionDir = sys_get_temp_dir() . '/live_sessions/';

        if (!is_dir($sessionDir)) {
            return;
        }

        $files = glob($sessionDir . '*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if ($data && isset($data['expires_at']) && $data['expires_at'] < time()) {
                unlink($file);
            }
        }
    }
}