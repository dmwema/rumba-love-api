<?php

namespace App\Controller\Api;

use App\DTO\PaymentConfirmRequest;
use App\Entity\AccessCode;
use App\Entity\Payment;
use App\Entity\User;
use App\Service\AccessCodeService;
use App\Service\PaymentService;
use App\Service\Billing\PaymentService as FlexPayService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Paiement")
 */
#[Route('/api/payments')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaymentService $paymentService,
        private FlexPayService $flexPayService,
        private AccessCodeService $accessCodeService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Initier un processus de paiement avec FlexPay
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"email", "fullName", "phone", "paymentMethod"},
     *         @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *         @OA\Property(property="fullName", type="string", example="John Doe"),
     *         @OA\Property(property="phone", type="string", example="243814063056"),
     *         @OA\Property(property="paymentMethod", type="string", enum={"card", "mobile"}, example="mobile")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Paiement initié avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="paymentId", type="integer", example=123),
     *         @OA\Property(property="status", type="string", example="pending"),
     *         @OA\Property(property="amount", type="string", example="10.00"),
     *         @OA\Property(property="paymentMethod", type="string", example="mobile"),
     *         @OA\Property(property="orderNumber", type="string", example="ORDER123456"),
     *         @OA\Property(property="redirectUrl", type="string", example="https://flexpay.cd/pay/..."),
     *         @OA\Property(property="message", type="string", example="Payment initiated with FlexPay")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Données invalides",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Missing required parameters")
     *     )
     * )
     */
    #[Route('/initiate', name: 'api_payment_initiate', methods: ['POST'])]
    public function initiate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des paramètres requis
        if (!$data ||
            !isset($data['email']) ||
            !isset($data['fullName']) ||
            !isset($data['phone']) ||
            !isset($data['paymentMethod'])) {
            return $this->json(['error' => 'Missing required parameters: email, fullName, phone, paymentMethod'], 400);
        }

        $email = trim($data['email']);
        $fullName = trim($data['fullName']);
        $phone = trim($data['phone']);
        $paymentMethod = trim($data['paymentMethod']);

        // Validation basique
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], 400);
        }

        if (!in_array($paymentMethod, ['card', 'mobile'])) {
            return $this->json(['error' => 'Invalid payment method. Must be "card" or "mobile"'], 400);
        }

        try {
            // Utiliser Doctrine EntityManager de manière professionnelle
            $this->logger->info('Starting payment initiation', [
                'email' => $email,
                'paymentMethod' => $paymentMethod
            ]);

            $this->entityManager->beginTransaction();

            try {
                // 1. Rechercher ou créer l'utilisateur
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFullName($fullName);
                    $user->setPhone($phone);
                    $user->setCreatedAt(new \DateTime());
                    $user->setIsOnline(false);
                    $user->setLastActivity(null);

                    $this->entityManager->persist($user);
                }

                // 2. Créer le paiement
                $payment = new Payment();
                $payment->setUser($user);
                $payment->setAmount('5.00');
                $payment->setPaymentMethod($paymentMethod);
                $payment->setStatus('pending');
                $payment->setCreatedAt(new \DateTime());

                $this->entityManager->persist($payment);
                $this->entityManager->flush(); // Sauvegarde pour obtenir l'ID

                // 3. Générer une référence unique pour FlexPay
                $reference = 'PAY-' . $payment->getId() . '-' . time();

                // 4. Créer l'objet operation pour FlexPay
                $operation = new class($phone, $reference, '5.00') {
                    private $phoneNumber;
                    private $reference;
                    private $amount;

                    public function __construct($phone, $reference, $amount) {
                        $this->phoneNumber = $phone;
                        $this->reference = $reference;
                        $this->amount = $amount;
                    }

                    public function getPhoneNumber() { return $this->phoneNumber; }
                    public function getReference() { return $this->reference; }
                    public function getAmount() { return $this->amount; }
                    public function getOrderNumber() { return $this->reference; }
                };

                // 5. Lancer le paiement FlexPay
                $flexpayResult = null;
                if ($paymentMethod === 'mobile') {
                    $flexpayResult = $this->flexPayService->mobilePayment($operation);
                } elseif ($paymentMethod === 'card') {
                    $flexpayResult = $this->flexPayService->cardPayment($operation);
                }

                // 6. Mettre à jour le paiement selon le résultat FlexPay
                if (!$flexpayResult || !$flexpayResult['success']) {
                    $payment->setStatus('failed');
                    $this->entityManager->flush();

                    // Rollback de la transaction en cas d'échec
                    $this->entityManager->rollback();

                    return $this->json([
                        'error' => 'Payment initiation failed',
                        'message' => $flexpayResult['message'] ?? 'Unknown error',
                        'paymentId' => $payment->getId()
                    ], 400);
                }

                // Paiement FlexPay réussi - mettre à jour avec les infos
                $payment->setTransactionReference($flexpayResult['orderNumber'] ?? $reference);
                $payment->setStatus('processing');
                $this->entityManager->flush();

                // Commit de la transaction
                $this->entityManager->commit();

                // 7. Retourner les informations de paiement
                $response = [
                    'paymentId' => $payment->getId(),
                    'status' => $payment->getStatus(),
                    'amount' => $payment->getAmount(),
                    'paymentMethod' => $payment->getPaymentMethod(),
                    'orderNumber' => $payment->getTransactionReference(),
                    'userId' => $user->getId(),
                    'message' => 'Payment initiated with FlexPay - Data persisted using Doctrine ORM'
                ];

                // Ajouter redirectUrl pour les paiements par carte
                if ($paymentMethod === 'card' && isset($flexpayResult['redirectUrl'])) {
                    $response['redirectUrl'] = $flexpayResult['redirectUrl'];
                }

                return $this->json($response, 200);

            } catch (\Exception $innerException) {
                // En cas d'erreur interne, rollback de la transaction
                $this->entityManager->rollback();
                throw $innerException;
            }

        } catch (\Exception $e) {
            // Log l'erreur de manière professionnelle
            $this->logger->error('Payment initiation failed', [
                'error' => $e->getMessage(),
                'email' => $email ?? 'unknown',
                'paymentMethod' => $paymentMethod ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => 'Internal server error',
                'message' => 'Payment processing failed due to a technical issue',
                'reference' => 'ERR-' . time()
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un paiement FlexPay
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"paymentId"},
     *         @OA\Property(property="paymentId", type="integer", example=123, description="ID du paiement à vérifier")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Statut du paiement récupéré",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="paymentId", type="integer", example=123),
     *         @OA\Property(property="status", type="string", example="success", enum={"success", "pending", "failed"}),
     *         @OA\Property(property="orderNumber", type="string", example="ORDER123456"),
     *         @OA\Property(property="flexpayStatus", type="object", description="Informations détaillées de FlexPay"),
     *         @OA\Property(property="accessCode", type="object", nullable=true, description="Code d'accès généré si paiement réussi",
     *             @OA\Property(property="code", type="string", example="LIVE-ABC123XYZ"),
     *             @OA\Property(property="expiresAt", type="string", format="date-time", example="2024-02-14T10:30:00Z"),
     *             @OA\Property(property="isUsed", type="boolean", example=false)
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Paiement non trouvé",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Payment not found")
     *     )
     * )
     */
    #[Route('/check-status', name: 'api_payment_check_status', methods: ['POST'])]
    public function checkPaymentStatus(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['paymentId'])) {
            return $this->json(['error' => 'paymentId is required'], 400);
        }

        $paymentId = (int) $data['paymentId'];

        try {
            // Récupérer le paiement depuis la base
            $payment = $this->entityManager->getRepository(Payment::class)->find($paymentId);

            if (!$payment) {
                return $this->json(['error' => 'Payment not found'], 404);
            }

            // Vérifier que le paiement a une référence de transaction
            $orderNumber = $payment->getTransactionReference();
            if (!$orderNumber) {
                return $this->json([
                    'error' => 'No transaction reference found for this payment',
                    'paymentId' => $paymentId,
                    'status' => $payment->getStatus()
                ], 400);
            }

            // Créer l'objet operation pour FlexPay (même interface que pour les autres méthodes)
            $operation = new class($orderNumber, $payment->getPaymentMethod() === 'mobile' ? $payment->getUser()->getPhone() : '243999999999') {
                private $orderNumber;
                private $phoneNumber;

                public function __construct($orderNumber, $phoneNumber) {
                    $this->orderNumber = $orderNumber;
                    $this->phoneNumber = $phoneNumber;
                }

                public function getOrderNumber() { return $this->orderNumber; }
                public function getPhoneNumber() { return $this->phoneNumber; }
                public function getReference() { return $this->orderNumber; }
                public function getAmount() { return '5.00'; } // Montant par défaut pour la vérification
            };

            // Vérifier le statut auprès de FlexPay
            try {
                $flexpayResult = $this->flexPayService->checkPaymentStatus($operation);
            } catch (\Exception $flexpayException) {
                // Si FlexPay échoue, retourner le statut actuel du paiement
                $this->logger->warning('FlexPay status check failed, returning current payment status', [
                    'paymentId' => $paymentId,
                    'orderNumber' => $orderNumber,
                    'error' => $flexpayException->getMessage()
                ]);

                return $this->json([
                    'paymentId' => $payment->getId(),
                    'status' => $payment->getStatus(),
                    'orderNumber' => $orderNumber,
                    'flexpayStatus' => [
                        'success' => null,
                        'waiting' => null,
                        'message' => 'Unable to check status with FlexPay: ' . $flexpayException->getMessage()
                    ],
                    'message' => 'Payment status retrieved from database (FlexPay check failed)'
                ], 200);
            }

            // Mettre à jour le statut du paiement si nécessaire
            $accessCode = null;
            $isNewPaymentSuccess = false;

            if (isset($flexpayResult['success']) && $flexpayResult['success'] && $payment->getStatus() !== 'success') {
                $payment->setStatus('success');
                $this->entityManager->flush();
                $isNewPaymentSuccess = true;

                // Générer un access code pour l'utilisateur si le paiement vient de réussir
                $user = $payment->getUser();

                // Vérifier si l'utilisateur a déjà un access code valide
                $existingAccessCode = $this->entityManager->getRepository(AccessCode::class)->findOneBy([
                    'user' => $user,
                    'isUsed' => false
                ], ['expiresAt' => 'DESC']);

                if (!$existingAccessCode || !$existingAccessCode->isValid()) {
                    // Générer un nouveau code d'accès
                    $accessCode = $this->accessCodeService->createAccessCodeForUser($user);
                    $this->logger->info('Access code generated for successful payment', [
                        'paymentId' => $paymentId,
                        'userId' => $user->getId(),
                        'accessCode' => $accessCode->getCode()
                    ]);
                } else {
                    // Utiliser le code existant
                    $accessCode = $existingAccessCode;
                    $this->logger->info('Using existing valid access code for successful payment', [
                        'paymentId' => $paymentId,
                        'userId' => $user->getId(),
                        'accessCode' => $accessCode->getCode()
                    ]);
                }
            } elseif (isset($flexpayResult['success']) && !$flexpayResult['success'] && !($flexpayResult['waiting'] ?? false) && $payment->getStatus() === 'pending') {
                $payment->setStatus('failed');
                $this->entityManager->flush();
            }

            // Préparer la réponse
            $response = [
                'paymentId' => $payment->getId(),
                'status' => $payment->getStatus(),
                'orderNumber' => $orderNumber,
                'flexpayStatus' => [
                    'success' => $flexpayResult['success'] ?? null,
                    'waiting' => $flexpayResult['waiting'] ?? null,
                    'message' => $flexpayResult['message'] ?? 'Status check completed'
                ],
                'message' => $isNewPaymentSuccess ? 'Payment confirmed successfully. Access code generated.' : 'Payment status checked successfully'
            ];

            // Ajouter l'access code si disponible
            if ($accessCode) {
                $response['accessCode'] = [
                    'code' => $accessCode->getCode(),
                    'expiresAt' => $accessCode->getExpiresAt()->format('Y-m-d H:i:s'),
                    'isUsed' => $accessCode->isUsed()
                ];
            }

            return $this->json($response, 200);

        } catch (\Exception $e) {
            $this->logger->error('Payment status check failed', [
                'error' => $e->getMessage(),
                'paymentId' => $paymentId,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => 'Internal server error',
                'message' => 'Failed to check payment status',
                'reference' => 'CHK-' . time()
            ], 500);
        }
    }

    /**
     * Confirmer un paiement et générer un code d'accès
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"paymentId"},
     *         @OA\Property(property="paymentId", type="integer", example=123)
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Paiement confirmé avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="paymentId", type="integer", example=123),
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="transactionReference", type="string", example="TXN-ABC123"),
     *         @OA\Property(property="message", type="string", example="Payment confirmed successfully. Access code generated.")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Paiement introuvable",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Payment not found or already processed")
     *     )
     * )
     */
    #[Route('/confirm', name: 'api_payment_confirm', methods: ['POST'])]
    public function confirm(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $dto = new PaymentConfirmRequest($data);
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $payment = $this->paymentService->confirmPayment($dto->paymentId);

        if (!$payment) {
            return $this->json(['error' => 'Payment not found or already processed'], 404);
        }

        return $this->json([
            'paymentId' => $payment->getId(),
            'status' => $payment->getStatus(),
            'transactionReference' => $payment->getTransactionReference(),
            'message' => 'Payment confirmed successfully. Access code generated.'
        ]);
    }
}