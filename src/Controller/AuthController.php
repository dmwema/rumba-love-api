<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AuthController
{
    public function __invoke(Request $request): JsonResponse
    {
        // Cette méthode est appelée par API Platform pour l'opération /login
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Email and password are required'
            ], 400);
        }

        $email = $data['email'];
        $password = $data['password'];

        // Pour l'instant, on utilise des credentials de test simples
        // En production, ceci devrait vérifier contre la base de données
        if (($email === 'fils@cinefilm.cd' || $email === 'c.sitta@imperatus-energy.com') && $password === 'p@ssword123654') {
            // Générer un token JWT simple qui sera accepté par notre système
            $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
            $payload = json_encode([
                'user_id' => 1,
                'username' => $email,
                'roles' => ['ROLE_ADMIN'],
                'iat' => time(),
                'exp' => time() + 3600 // 1 heure
            ]);

            $headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            // Utiliser une clé connue pour que les tokens soient prévisibles pour les tests
            $secret = 'test_secret_key_for_development_only';
            $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret);
            $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;

            return new JsonResponse([
                'token' => $jwt,
                'user' => [
                    'id' => 1,
                    'email' => $email,
                    'fullName' => 'Admin User'
                ],
                'message' => 'Authentication successful',
                'expires_in' => 3600
            ]);
        }

        return new JsonResponse([
            'error' => 'Invalid credentials'
        ], 401);
    }
}