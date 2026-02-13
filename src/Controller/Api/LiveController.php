<?php

namespace App\Controller\Api;

use App\Entity\LiveEvent;
use App\Service\StreamUrlEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Accès Live")
 */
#[Route('/api/live')]
class LiveController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StreamUrlEncryptionService $encryptionService
    ) {}

    /**
     * Accéder au stream en direct
     *
     * @OA\Response(
     *     response=200,
     *     description="Accès au stream accordé",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="streamUrl", type="string", example="https://configured-stream-url.com/live"),
     *         @OA\Property(property="title", type="string", example="Concert Live Streaming"),
     *         @OA\Property(property="isLive", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Stream access granted")
     *     )
     * )
     * @OA\Response(
     *     response=500,
     *     description="URL du stream non configurée",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Stream URL not configured")
     *     )
     * )
     */
    #[Route('/watch', name: 'api_live_watch', methods: ['GET'])]
    public function watch(): JsonResponse
    {
        // Accès public au stream

        // Récupérer l'URL du stream depuis la variable d'environnement
        $streamUrl = $_ENV['STREAM_URL'] ?? getenv('STREAM_URL');

        if (!$streamUrl) {
            return $this->json(['error' => 'Stream URL not configured'], 500);
        }

        // Validation basique de l'URL
        if (!filter_var($streamUrl, FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Invalid stream URL configuration'], 500);
        }

        // Pour l'instant, on considère que le stream est toujours actif
        // TODO: Ajouter une logique pour vérifier si le stream est réellement en ligne
        $isLive = true;

        return $this->json([
            'streamUrl' => $streamUrl,
            'title' => 'Concert Live Streaming',
            'isLive' => $isLive,
            'message' => 'Stream access granted'
        ]);
    }
}