<?php

namespace App\Controller\Api;

use App\Entity\AccessCode;
use App\Entity\LiveEvent;
use App\Service\StreamUrlEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * Accéder au stream en direct avec validation du code d'accès
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"code"},
     *         @OA\Property(property="code", type="string", example="CINE-9C52QW4", description="Code d'accès valide")
     *     )
     * )
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
     *     response=400,
     *     description="Code d'accès invalide ou manquant",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Invalid or expired access code")
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
    #[Route('/watch', name: 'api_live_watch', methods: ['POST'])]
    public function watch(Request $request): JsonResponse
    {
        // Récupérer et valider le code d'accès
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['code']) || empty($data['code'])) {
            return $this->json(['error' => 'Access code is required'], 400);
        }

        $code = trim($data['code']);

        // Valider le code d'accès
        $accessCode = $this->entityManager->getRepository(AccessCode::class)->findOneBy(['code' => $code]);

        if (!$accessCode) {
            return $this->json(['error' => 'Invalid access code'], 400);
        }

        if (!$accessCode->isValid()) {
            return $this->json(['error' => 'Access code has expired or already used'], 400);
        }

        // Marquer le code comme utilisé et mettre à jour l'utilisateur
        $accessCode->markAsUsed();
        $user = $accessCode->getUser();
        $user->setIsOnline(true);
        $user->setLastActivity(new \DateTime());
        $this->entityManager->flush();

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
        $isLive = true;

        return $this->json([
            'streamUrl' => $streamUrl,
            'title' => 'Concert Live Streaming',
            'isLive' => $isLive,
            'message' => 'Stream access granted',
            'user' => [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'email' => $user->getEmail()
            ]
        ]);
    }
}