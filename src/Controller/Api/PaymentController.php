<?php

namespace App\Controller\Api;

use App\DTO\PaymentConfirmRequest;
use App\Entity\Payment;
use App\Entity\User;
use App\Service\PaymentService;
use App\Service\Billing\PaymentService as FlexPayService;
use Doctrine\ORM\EntityManagerInterface;
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
        private ValidatorInterface $validator
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
        // Log pour debug
        error_log('PaymentController::initiate called');

        $data = json_decode($request->getContent(), true);
        error_log('Request data: ' . json_encode($data));

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
            // Utiliser PDO directement pour la persistance (plus fiable que Doctrine pour ce cas)
            $dbPath = dirname(__DIR__, 3) . '/var/data.db';
            $pdo = new \PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // 1. Enregistrer l'utilisateur (avec déduplication par email)
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (email, full_name, phone, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $fullName, $phone, date('Y-m-d H:i:s')]);
            $userId = $pdo->lastInsertId();

            // Si l'utilisateur existait déjà, récupérer son ID
            if ($userId == 0) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $userId = $stmt->fetchColumn();
            }

            // 2. Créer le paiement
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, status, payment_method, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, '10.00', 'pending', $paymentMethod, date('Y-m-d H:i:s')]);
            $paymentId = $pdo->lastInsertId();

            // 3. Générer une référence unique pour FlexPay
            $reference = 'PAY-' . $paymentId . '-' . time();

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

            // 6. Mettre à jour le statut selon le résultat FlexPay
            $finalStatus = 'pending';
            if (!$flexpayResult || !$flexpayResult['success']) {
                $finalStatus = 'failed';
                $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
                $stmt->execute([$paymentId]);

                return $this->json([
                    'error' => 'Payment initiation failed',
                    'message' => $flexpayResult['message'] ?? 'Unknown error',
                    'paymentId' => $paymentId
                ], 400);
            }

            // Paiement FlexPay réussi - mettre à jour avec les infos
            $orderNumber = $flexpayResult['orderNumber'] ?? $reference;
            $stmt = $pdo->prepare("UPDATE payments SET transaction_reference = ?, status = 'processing' WHERE id = ?");
            $stmt->execute([$orderNumber, $paymentId]);

            // 7. Retourner les informations de paiement
            $response = [
                'paymentId' => $paymentId,
                'status' => 'processing',
                'amount' => '10.00',
                'paymentMethod' => $paymentMethod,
                'orderNumber' => $orderNumber,
                'userId' => $userId,
                'message' => 'Payment initiated with FlexPay - Data persisted'
            ];

            // Ajouter redirectUrl pour les paiements par carte
            if ($paymentMethod === 'card' && isset($flexpayResult['redirectUrl'])) {
                $response['redirectUrl'] = $flexpayResult['redirectUrl'];
            }

            return $this->json($response, 200);

        } catch (\Exception $e) {
            error_log('Doctrine error in payment initiation: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // En cas d'erreur Doctrine, retourner une réponse d'erreur détaillée
            return $this->json([
                'error' => 'Database error',
                'message' => 'Failed to process payment due to database issues',
                'details' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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