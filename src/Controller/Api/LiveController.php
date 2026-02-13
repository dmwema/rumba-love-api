<?php

namespace App\Controller\Api;

use App\Entity\AccessCode;
use App\Entity\LiveEvent;
use App\Service\LiveSessionService;
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
        private LiveSessionService $liveSessionService,
        private StreamUrlEncryptionService $encryptionService
    ) {}

    /**
     * Accéder au stream en direct avec validation du code d'accès ou token de session
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         oneOf={
     *             @OA\Schema(
     *                 required={"code"},
     *                 @OA\Property(property="code", type="string", example="CINE-9C52QW4", description="Code d'accès valide (première utilisation)")
     *             ),
     *             @OA\Schema(
     *                 required={"sessionToken"},
     *                 @OA\Property(property="sessionToken", type="string", example="abc123def456", description="Token de session (utilisations suivantes)")
     *             )
     *         }
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
     *         @OA\Property(property="message", type="string", example="Stream access granted"),
     *         @OA\Property(property="sessionToken", type="string", example="abc123def456", description="Token de session pour les prochaines connexions")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Code d'accès ou token invalide",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Invalid access code or session token")
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
        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Request body is required'], 400);
        }

        $user = null;
        $sessionToken = null;

        // Vérifier si un token de session est fourni
        if (isset($data['sessionToken']) && !empty($data['sessionToken'])) {
            $sessionTokenValue = trim($data['sessionToken']);

            // Valider le token de session
            $user = $this->liveSessionService->validateSessionToken($sessionTokenValue);

            if (!$user) {
                return $this->json(['error' => 'Invalid or expired session token'], 400);
            }

            // Générer un nouveau token de session pour prolonger la session
            $sessionToken = $this->liveSessionService->generateSessionToken($user);

        } elseif (isset($data['code']) && !empty($data['code'])) {
            // Validation par code d'accès (première utilisation)
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

            // Générer un token de session pour les futures connexions
            $sessionToken = $this->liveSessionService->generateSessionToken($user);

            $this->entityManager->flush();
        } else {
            return $this->json(['error' => 'Access code or session token is required'], 400);
        }

        // Mettre à jour le statut en ligne de l'utilisateur
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
            'sessionToken' => $sessionToken,
            'user' => [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'email' => $user->getEmail()
            ]
        ]);
    }
}