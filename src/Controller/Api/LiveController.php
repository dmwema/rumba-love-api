<?php

namespace App\Controller\Api;

use App\Entity\LiveEvent;
use App\Service\StreamUrlEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT"
     * )
     * @OA\Response(
     *     response=200,
     *     description="Accès au stream accordé",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="streamUrl", type="string", example="https://real-stream-url.com/live"),
     *         @OA\Property(property="title", type="string", example="Concert Live - Artiste Mystère"),
     *         @OA\Property(property="isLive", type="boolean", example=true)
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Token manquant ou invalide",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Missing or invalid authorization header")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Aucun événement actif",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="No active live event")
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/watch', name: 'api_live_watch', methods: ['GET'])]
    #[IsGranted('ROLE_LIVE_ACCESS')]
    public function watch(): JsonResponse
    {
        // Le token JWT est automatiquement validé par le système de sécurité
        // avec #[IsGranted('ROLE_LIVE_ACCESS')]

        // Récupérer l'événement live actif
        $event = $this->entityManager->getRepository(LiveEvent::class)->findOneBy(['isActive' => true]);

        if (!$event) {
            return $this->json(['error' => 'No active live event'], 404);
        }

        try {
            // Déchiffrer l'URL du stream
            $streamUrl = $this->encryptionService->decrypt($event->getStreamUrl());
        } catch (\RuntimeException $e) {
            return $this->json(['error' => 'Unable to access stream'], 500);
        }

        return $this->json([
            'streamUrl' => $streamUrl,
            'title' => $event->getTitle(),
            'isLive' => $event->isLiveNow()
        ]);
    }
}