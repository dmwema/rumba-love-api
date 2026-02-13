<?php

namespace App\Controller\Api;

use App\DTO\PaymentInitiateRequest;
use App\DTO\PaymentConfirmRequest;
use App\Entity\User;
use App\Service\PaymentService;
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
#[Route('/api/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaymentService $paymentService,
        private ValidatorInterface $validator
    ) {}

    /**
     * Initier un processus de paiement
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"email", "fullName", "paymentMethod"},
     *         @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *         @OA\Property(property="fullName", type="string", example="John Doe"),
     *         @OA\Property(property="phone", type="string", example="+243123456789"),
     *         @OA\Property(property="paymentMethod", type="string", enum={"card", "mobile"}, example="card")
     *     )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Paiement initié avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="paymentId", type="integer", example=123),
     *         @OA\Property(property="status", type="string", example="pending"),
     *         @OA\Property(property="amount", type="string", example="10.00"),
     *         @OA\Property(property="paymentMethod", type="string", example="card"),
     *         @OA\Property(property="message", type="string", example="Payment initiated successfully")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Données invalides",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *     )
     * )
     */
    #[Route('/initiate', name: 'api_payment_initiate', methods: ['POST'])]
    public function initiate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $dto = new PaymentInitiateRequest($data);
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Trouver ou créer l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $dto->email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($dto->email);
            $user->setFullName($dto->fullName);
            $user->setPhone($dto->phone);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Créer le paiement
        $payment = $this->paymentService->initiatePayment($user, '10.00', $dto->paymentMethod);

        return $this->json([
            'paymentId' => $payment->getId(),
            'status' => $payment->getStatus(),
            'amount' => $payment->getAmount(),
            'paymentMethod' => $payment->getPaymentMethod(),
            'message' => 'Payment initiated successfully'
        ], 201);
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