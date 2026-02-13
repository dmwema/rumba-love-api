<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class TestController
{
    #[Route('/test-api', name: 'api_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'API Live Streaming is working!',
            'timestamp' => time(),
            'endpoints' => [
                'login' => 'POST /auth/admin',
                'users' => 'GET /api/admin/users (requires JWT)',
                'payments' => 'GET /api/admin/payments (requires JWT)',
                'event' => 'GET /api/event',
                'docs' => 'GET /api/docs (Swagger UI)'
            ],
            'test_credentials' => [
                'username' => 'fils@cinefilm.cd',
                'password' => 'p@ssword123654'
            ]
        ]);
    }
}