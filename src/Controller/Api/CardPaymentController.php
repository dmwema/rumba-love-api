<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Entity\User;
use App\Service\AccessCodeService;
use App\Service\Billing\PaymentService as FlexPayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Paiement Carte")
 */
#[Route('/api/card-payments')]
class CardPaymentController extends AbstractController
{
    private const EVENT_PRICE = '5.00'; // Prix fixe du concert

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FlexPayService $flexPayService,
        private AccessCodeService $accessCodeService
    ) {}

    /**
     * Initier un paiement par carte bancaire
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"email", "fullName"},
     *         @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email address of the user"),
     *         @OA\Property(property="fullName", type="string", example="John Doe", description="Full name of the user")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Paiement par carte initié avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="paymentId", type="integer", example=123),
     *         @OA\Property(property="status", type="string", example="pending"),
     *         @OA\Property(property="amount", type="string", example="5.00"),
     *         @OA\Property(property="paymentMethod", type="string", example="card"),
     *         @OA\Property(property="orderNumber", type="string", example="ORDER123456"),
     *         @OA\Property(property="redirectUrl", type="string", example="https://cardpayment.flexpay.cd/pay/ORDER123456"),
     *         @OA\Property(property="message", type="string", example="Card payment initiated. Redirect user to FlexPay.")
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
     * @OA\Response(
     *     response=500,
     *     description="Erreur interne du serveur",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Failed to initiate card payment")
     *     )
     * )
     */
    #[Route('/initiate', name: 'api_card_payment_initiate', methods: ['POST'])]
    public function initiate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        // Validation basique des champs requis
        $requiredFields = ['email', 'fullName'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return $this->json(['error' => "Missing required field: {$field}"], 400);
            }
        }

        // Validation basique de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], 400);
        }



        try {
            // 1. Rechercher ou créer l'utilisateur
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);

            if (!$user) {
                $user = new User();
                $user->setEmail($data['email']);
                $user->setFullName($data['fullName']);
                $user->setCreatedAt(new \DateTime());
                $user->setIsOnline(false);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            // 2. Créer le paiement
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setAmount(self::EVENT_PRICE);
            $payment->setPaymentMethod('card');
            $payment->setPhoneNumber('CARD_PAYMENT'); // Valeur par défaut pour les cartes
            $payment->setStatus('pending');
            $payment->setCreatedAt(new \DateTime());

            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            // 3. Générer une référence unique pour FlexPay
            $reference = 'CARD-' . $payment->getId() . '-' . time();

            // 4. Créer l'objet operation pour FlexPay
            $operation = new class($reference, self::EVENT_PRICE) {
                private $reference;
                private $amount;

                public function __construct($reference, $amount) {
                    $this->reference = $reference;
                    $this->amount = $amount;
                }

                public function getReference() { return $this->reference; }
                public function getAmount() { return $this->amount; }
                public function getOrderNumber() { return $this->reference; }
            };

            // 5. Lancer le paiement FlexPay
            $flexpayResult = $this->flexPayService->cardPayment($operation);

            if (!$flexpayResult['success']) {
                $payment->setStatus('failed');
                $this->entityManager->flush();

                return $this->json([
                    'error' => 'Card payment initiation failed',
                    'message' => $flexpayResult['message'] ?? 'Unknown error'
                ], 400);
            }

            // Paiement FlexPay réussi - mettre à jour avec les infos
            $payment->setTransactionReference($flexpayResult['orderNumber']);
            $payment->setStatus('processing');
            $this->entityManager->flush();

            // 6. Retourner les informations de paiement avec l'URL de redirection
            $response = [
                'paymentId' => $payment->getId(),
                'status' => $payment->getStatus(),
                'amount' => $payment->getAmount(),
                'paymentMethod' => $payment->getPaymentMethod(),
                'orderNumber' => $payment->getTransactionReference(),
                'redirectUrl' => $flexpayResult['redirectUrl'],
                'message' => $flexpayResult['message']
            ];

            return $this->json($response, 200);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error',
                'message' => 'Failed to initiate card payment'
            ], 500);
        }
    }

    /**
     * Callback pour les paiements par carte (appelé par FlexPay)
     *
     * @OA\Parameter(
     *     name="orderNumber",
     *     in="query",
     *     description="Numéro de commande FlexPay",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="Statut du paiement",
     *     required=true,
     *     @OA\Schema(type="string", enum={"success", "failed", "cancelled"})
     * )
     * @OA\Response(
     *     response=200,
     *     description="Callback traité avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="message", type="string", example="Payment callback processed")
     *     )
     * )
     */
    #[Route('/callback', name: 'api_card_payment_callback', methods: ['GET', 'POST'])]
    public function callback(Request $request): JsonResponse
    {
        $orderNumber = $request->query->get('orderNumber') ?? $request->request->get('orderNumber');
        $status = $request->query->get('status') ?? $request->request->get('status');

        if (!$orderNumber) {
            return $this->json(['error' => 'Missing orderNumber'], 400);
        }

        try {
            // Trouver le paiement par référence de transaction
            $payment = $this->entityManager->getRepository(Payment::class)->findOneBy([
                'transactionReference' => $orderNumber
            ]);

            if (!$payment) {
                return $this->json(['error' => 'Payment not found'], 404);
            }

            // Créer un objet operation pour vérifier le statut auprès de FlexPay
            $operation = new class($orderNumber, $payment->getAmount()) {
                private $orderNumber;
                private $amount;

                public function __construct($orderNumber, $amount) {
                    $this->orderNumber = $orderNumber;
                    $this->amount = $amount;
                }

                public function getOrderNumber() { return $this->orderNumber; }
                public function getAmount() { return $this->amount; }
                public function getPhoneNumber() { return 'CARD_PAYMENT'; } // Pour les cartes, pas de numéro de téléphone
            };

            // Vérifier le statut réel auprès de FlexPay
            $flexpayResult = $this->flexPayService->checkPaymentStatus($operation);

            $accessCode = null;
            $isNewPaymentSuccess = false;

            // Mettre à jour le statut basé sur la réponse de FlexPay
            if (isset($flexpayResult['success']) && $flexpayResult['success'] === true) {
                if ($payment->getStatus() !== 'success') {
                    $payment->setStatus('success');
                    $isNewPaymentSuccess = true;
                }
            } elseif (isset($flexpayResult['success']) && $flexpayResult['success'] === false) {
                if ($payment->getStatus() !== 'failed') {
                    $payment->setStatus('failed');
                }
            }
            // Si FlexPay indique que c'est en attente, on ne change pas le statut

            $this->entityManager->flush();

            // Générer un access code pour l'utilisateur si le paiement vient de réussir
            if ($isNewPaymentSuccess) {
                $user = $payment->getUser();
                $existingAccessCode = $this->entityManager->getRepository(\App\Entity\AccessCode::class)->findOneBy([
                    'user' => $user,
                    'isUsed' => false
                ], ['expiresAt' => 'DESC']);

                if (!$existingAccessCode || !$existingAccessCode->isValid()) {
                    $accessCode = $this->accessCodeService->createAccessCodeForUser($user);
                } else {
                    $accessCode = $existingAccessCode;
                }
            }

            $response = [
                'message' => 'Payment callback processed via FlexPay verification',
                'orderNumber' => $orderNumber,
                'status' => $payment->getStatus(),
                'flexpayStatus' => [
                    'success' => $flexpayResult['success'] ?? null,
                    'waiting' => $flexpayResult['waiting'] ?? null,
                    'message' => $flexpayResult['message'] ?? 'Status check completed'
                ]
            ];

            if ($accessCode) {
                $response['accessCode'] = [
                    'code' => $accessCode->getCode(),
                    'expiresAt' => $accessCode->getExpiresAt()->format('Y-m-d H:i:s'),
                    'isUsed' => $accessCode->isUsed()
                ];
            }

            return $this->json($response, 200);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Callback processing failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}