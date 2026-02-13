<?php

namespace App\Controller\Api;

use App\Entity\AdminUser;
use App\Entity\AccessCode;
use App\Entity\LiveEvent;
use App\Entity\Payment;
use App\Entity\User;
use App\Service\LiveAccessTokenService;
use App\Service\StreamUrlEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Administration")
 */
#[Route('/api/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private StreamUrlEncryptionService $encryptionService,
        private LiveAccessTokenService $liveAccessTokenService
    ) {}

    /**
     * Authentification administrateur
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"username", "password"},
     *         @OA\Property(property="username", type="string", example="fils@cinefilm.cd"),
     *         @OA\Property(property="password", type="string", example="p@ssword123654")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Authentification réussie",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Identifiants invalides",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Invalid credentials")
     *     )
     * )
     */
    #[Route('/../../../auth/admin', name: 'api_admin_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['username']) || !isset($data['password'])) {
            return $this->json(['error' => 'Username and password are required'], 400);
        }

        $username = $data['username'];
        $password = $data['password'];

        // Vérification simple pour les credentials de test
        if ($username === 'fils@cinefilm.cd' && $password === 'p@ssword123654') {
            // Générer un token JWT simple
            $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
            $payload = json_encode([
                'user_id' => 1,
                'username' => $username,
                'roles' => ['ROLE_ADMIN'],
                'iat' => time(),
                'exp' => time() + 3600 // 1 heure
            ]);

            $headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            // Signature simple (pas sécurisée pour la production)
            $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, 'your-secret-key');
            $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;

            return $this->json([
                'token' => $jwt,
                'message' => 'Authentication successful',
                'expires_in' => 3600
            ]);
        }

        return $this->json(['error' => 'Invalid credentials'], 401);
    }

    /**
     * Tester la configuration de l'API (temporaire - public)
     */
    #[Route('/test', name: 'api_admin_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json([
            'message' => 'API Admin is working!',
            'timestamp' => time(),
            'login_endpoint' => '/api/admin/login',
            'test_credentials' => [
                'username' => 'fils@cinefilm.cd',
                'password' => 'p@ssword123654'
            ]
        ]);
    }

    /**
     * Lister tous les utilisateurs (TEST PUBLIC)
     *
     * @OA\Response(
     *     response=200,
     *     description="Liste des utilisateurs retournée",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="fullName", type="string", example="John Doe"),
     *             @OA\Property(property="phone", type="string", example="+243123456789"),
     *             @OA\Property(property="createdAt", type="string", format="date-time"),
     *             @OA\Property(property="paymentsCount", type="integer", example=1),
     *             @OA\Property(property="accessCodesCount", type="integer", example=1)
     *         )
     *     )
     * )
     */
    #[Route('/users', name: 'api_admin_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        error_log('AdminController::getUsers called');
        return $this->json(['message' => 'Users endpoint reached', 'timestamp' => time()]);
    }

    /**
     * Lister tous les paiements
     *
     * @OA\Response(
     *     response=200,
     *     description="Liste des paiements retournée",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="fullName", type="string", example="John Doe")
     *             ),
     *             @OA\Property(property="amount", type="string", example="10.00"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="paymentMethod", type="string", example="card"),
     *             @OA\Property(property="transactionReference", type="string", example="TXN-ABC123"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/payments', name: 'api_admin_payments', methods: ['GET'])]
    public function getPayments(): JsonResponse
    {
        $payments = $this->entityManager->getRepository(Payment::class)->findBy([], ['createdAt' => 'DESC']);

        $data = array_map(function (Payment $payment) {
            return [
                'id' => $payment->getId(),
                'user' => [
                    'id' => $payment->getUser()->getId(),
                    'email' => $payment->getUser()->getEmail(),
                    'fullName' => $payment->getUser()->getFullName(),
                ],
                'amount' => $payment->getAmount(),
                'status' => $payment->getStatus(),
                'paymentMethod' => $payment->getPaymentMethod(),
                'transactionReference' => $payment->getTransactionReference(),
                'createdAt' => $payment->getCreatedAt()->format('c'),
            ];
        }, $payments);

        return $this->json($data);
    }

    #[Route('/accesscodes', name: 'api_admin_access_codes', methods: ['GET'])]
    public function getAccessCodes(): JsonResponse
    {
        $accessCodes = $this->entityManager->getRepository(AccessCode::class)->findBy([], ['createdAt' => 'DESC']);

        $data = array_map(function (AccessCode $accessCode) {
            return [
                'id' => $accessCode->getId(),
                'user' => [
                    'id' => $accessCode->getUser()->getId(),
                    'email' => $accessCode->getUser()->getEmail(),
                ],
                'code' => $accessCode->getCode(),
                'isUsed' => $accessCode->isUsed(),
                'usedAt' => $accessCode->getUsedAt()?->format('c'),
                'expiresAt' => $accessCode->getExpiresAt()->format('c'),
                'createdAt' => $accessCode->getCreatedAt()->format('c'),
            ];
        }, $accessCodes);

        return $this->json($data);
    }

    /**
     * OBSOLÈTE : Configuration du stream via variable d'environnement
     *
     * L'URL du stream est maintenant configurée via la variable d'environnement STREAM_URL.
     * Cette méthode retourne les informations de configuration actuelles.
     *
     * @OA\Response(
     *     response=200,
     *     description="Informations de configuration du stream",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", type="string", example="Stream URL is configured via STREAM_URL environment variable"),
     *         @OA\Property(property="currentUrl", type="string", example="https://configured-stream-url.com/live"),
     *         @OA\Property(property="configMethod", type="string", example="environment_variable"),
     *         @OA\Property(property="note", type="string", example="Modify the STREAM_URL environment variable to change the stream URL")
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/event/update-stream', name: 'api_admin_event_update_stream', methods: ['PUT'])]
    public function updateStreamUrl(Request $request): JsonResponse
    {
        $streamUrl = $_ENV['STREAM_URL'] ?? getenv('STREAM_URL') ?? 'Not configured';

        return $this->json([
            'message' => 'Stream URL is configured via STREAM_URL environment variable',
            'currentUrl' => $streamUrl,
            'configMethod' => 'environment_variable',
            'note' => 'Modify the STREAM_URL environment variable to change the stream URL',
            'example' => 'STREAM_URL=https://your-stream-platform.com/live/concert'
        ]);
    }

    /**
     * Récupération hautement sécurisée du stream (Double authentification)
     *
     * Nécessite : Token Admin + Token Live Access + Validation temps réel
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"liveToken", "userId", "sessionId"},
     *         @OA\Property(property="liveToken", type="string", description="Token d'accès live valide"),
     *         @OA\Property(property="userId", type="integer", description="ID de l'utilisateur"),
     *         @OA\Property(property="sessionId", type="string", description="ID de session unique")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Accès stream accordé avec sécurité maximale",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="streamUrl", type="string"),
     *         @OA\Property(property="accessGranted", type="boolean", example=true),
     *         @OA\Property(property="expiresIn", type="integer", example=300),
     *         @OA\Property(property="securityLevel", type="string", example="MAXIMUM")
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     description="Accès refusé - sécurité compromise",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Security breach detected")
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    /**
     * OBSOLÈTE : Accès stream simplifié
     *
     * L'accès au stream se fait maintenant directement via GET /api/live/watch
     * avec le token d'accès live obtenu après validation du code.
     *
     * @OA\Response(
     *     response=200,
     *     description="Informations sur l'accès au stream",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", type="string", example="Use GET /api/live/watch with live access token"),
     *         @OA\Property(property="streamEndpoint", type="string", example="/api/live/watch"),
     *         @OA\Property(property="note", type="string", example="Stream URL is configured via STREAM_URL environment variable")
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/stream/secure-access', name: 'api_admin_secure_stream_access', methods: ['POST'])]
    public function getSecureStreamAccess(Request $request): JsonResponse
    {
        return $this->json([
            'message' => 'Use GET /api/live/watch with live access token',
            'streamEndpoint' => '/api/live/watch',
            'note' => 'Stream URL is configured via STREAM_URL environment variable',
            'authentication' => 'Use live access token obtained from code validation'
        ]);
    }

    /**
     * Créer un événement de test (endpoint temporaire pour debug)
     *
     * @OA\Response(
     *     response=201,
     *     description="Événement de test créé",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="eventId", type="integer")
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/create-test-event', name: 'api_admin_create_test_event', methods: ['POST'])]
    // Temporairement sans authentification pour permettre les tests
    public function createTestEvent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $event = new LiveEvent();
            $event->setTitle($data['title'] ?? 'Test Event');
            $event->setDescription($data['description'] ?? 'Test description');
            $event->setPrice($data['price'] ?? 10.00);
            $event->setStreamUrl($this->encryptionService->encrypt($data['streamUrl'] ?? 'https://test.com'));
            $event->setLiveDate(new \DateTimeImmutable($data['liveDate'] ?? 'now +1 day'));
            $event->setIsActive(true);
            $event->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Test event created successfully',
                'eventId' => $event->getId()
            ], 201);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create test event: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/event/activate', name: 'api_admin_event_activate', methods: ['PUT'])]
    public function activateEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $isActive = $data['isActive'] ?? true;

        $event = $this->entityManager->getRepository(LiveEvent::class)->findOneBy([], ['id' => 'DESC']);

        if (!$event) {
            return $this->json(['error' => 'No live event found'], 404);
        }

        $event->setIsActive($isActive);
        $this->entityManager->flush();

        return $this->json([
            'message' => $isActive ? 'Event activated successfully' : 'Event deactivated successfully',
            'isActive' => $event->isActive()
        ]);
    }
}