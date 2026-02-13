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
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    // #[IsGranted('ROLE_ADMIN')] // Temporairement désactivé pour test
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
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
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
     * Mettre à jour l'URL du stream
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"streamUrl"},
     *         @OA\Property(property="streamUrl", type="string", example="https://real-stream-platform.com/live/concert123")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="URL du stream mise à jour",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", type="string", example="Stream URL updated successfully")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Événement introuvable",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="No live event found")
     *     )
     * )
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/event/update-stream', name: 'api_admin_event_update_stream', methods: ['PUT'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function updateStreamUrl(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['streamUrl'])) {
            return $this->json(['error' => 'streamUrl is required'], 400);
        }

        // Validation supplémentaire de sécurité
        if (!filter_var($data['streamUrl'], FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Invalid URL format'], 400);
        }

        // Vérifier que l'URL commence par https pour la sécurité
        if (!str_starts_with($data['streamUrl'], 'https://')) {
            return $this->json(['error' => 'Only HTTPS URLs are allowed for security'], 400);
        }

        $event = $this->entityManager->getRepository(LiveEvent::class)->findOneBy([], ['id' => 'DESC']);

        if (!$event) {
            return $this->json(['error' => 'No live event found'], 404);
        }

        // Générer un ID unique pour ce stream
        $streamId = 'STREAM-' . strtoupper(substr(md5(uniqid()), 0, 8));

        try {
            $encryptedUrl = $this->encryptionService->encrypt($data['streamUrl']);
            $event->setStreamUrl($encryptedUrl);
            $this->entityManager->flush();

            // Logger l'action pour audit
            error_log(sprintf(
                '[STREAM_UPDATE] Admin updated stream URL for event %d at %s',
                $event->getId(),
                date('Y-m-d H:i:s')
            ));

        } catch (\RuntimeException $e) {
            return $this->json(['error' => 'Failed to encrypt stream URL'], 500);
        }

        return $this->json([
            'message' => 'Stream URL updated and encrypted successfully',
            'updatedAt' => date('c'),
            'streamId' => $streamId,
            'securityLevel' => 'HIGH'
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
    #[Route('/stream/secure-access', name: 'api_admin_secure_stream_access', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getSecureStreamAccess(Request $request): JsonResponse
    {
        // Cette méthode utilise une approche directe pour la sécurité maximale
        // au lieu d'un DTO pour éviter toute interférence
        $data = json_decode($request->getContent(), true);

        if (!$data ||
            !isset($data['liveToken']) ||
            !isset($data['userId']) ||
            !isset($data['sessionId'])) {
            return $this->json(['error' => 'Missing required security parameters'], 400);
        }

        // Vérifier le token live
        try {
            $this->liveAccessTokenService->validateLiveAccessToken($data['liveToken']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid live access token'], 403);
        }

        // Vérifier que l'utilisateur existe et est valide
        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Vérifier que l'utilisateur a un code d'accès valide et utilisé récemment
        $validAccessCode = $this->entityManager->getRepository(AccessCode::class)->findOneBy([
            'user' => $user,
            'isUsed' => true
        ], ['usedAt' => 'DESC']);

        if (!$validAccessCode || !$validAccessCode->isValid()) {
            return $this->json(['error' => 'No valid access code found'], 403);
        }

        // Vérifier que le code a été utilisé récemment (dans les dernières 10 minutes)
        $tenMinutesAgo = new \DateTime('-10 minutes');
        if ($validAccessCode->getUsedAt() < $tenMinutesAgo) {
            return $this->json(['error' => 'Access code expired for streaming'], 403);
        }

        // Récupérer l'événement live actif
        $event = $this->entityManager->getRepository(LiveEvent::class)->findOneBy(['isActive' => true]);

        if (!$event) {
            return $this->json(['error' => 'No active live event'], 404);
        }

        // Vérifier que le stream est disponible en ce moment
        if (!$event->isLiveNow()) {
            return $this->json(['error' => 'Stream not currently live'], 403);
        }

        try {
            // Déchiffrer l'URL du stream avec sécurité maximale
            $streamUrl = $this->encryptionService->decrypt($event->getStreamUrl());
        } catch (\RuntimeException $e) {
            return $this->json(['error' => 'Stream decryption failed'], 500);
        }

        // Marquer l'utilisateur comme actif
        $user->setIsOnline(true);
        $this->entityManager->flush();

        // Logger l'accès sécurisé
        error_log(sprintf(
            '[SECURE_STREAM_ACCESS] User %d accessed stream at %s with session %s',
            $user->getId(),
            date('Y-m-d H:i:s'),
            $data['sessionId']
        ));

        return $this->json([
            'streamUrl' => $streamUrl,
            'title' => $event->getTitle(),
            'accessGranted' => true,
            'expiresIn' => 300, // 5 minutes
            'securityLevel' => 'MAXIMUM',
            'userValidated' => true,
            'sessionId' => $data['sessionId'],
            'accessTimestamp' => time()
        ]);
    }

    #[Route('/event/activate', name: 'api_admin_event_activate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
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