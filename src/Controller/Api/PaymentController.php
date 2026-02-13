<?php

namespace App\Controller\Api;

use App\DTO\PaymentConfirmRequest;
use App\Entity\Payment;
use App\Entity\User;
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
                $payment->setAmount('10.00');
                $payment->setPaymentMethod($paymentMethod);
                $payment->setStatus('pending');
                $payment->setCreatedAt(new \DateTime());

                $this->entityManager->persist($payment);
                $this->entityManager->flush(); // Sauvegarde pour obtenir l'ID

                // 3. Générer une référence unique pour FlexPay
                $reference = 'PAY-' . $payment->getId() . '-' . time();

                // 4. Créer l'objet operation pour FlexPay
                $operation = new class($phone, $reference, '10.00') {
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