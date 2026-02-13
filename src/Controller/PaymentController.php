<?php

namespace App\Controller;

use App\DTO\PaymentInitiateRequest;
use App\DTO\PaymentConfirmRequest;
use App\DTO\PaymentResponse;
use App\Entity\Payment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PaymentController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function initiatePayment(PaymentInitiateRequest $data): PaymentResponse
    {
        // Trouver ou créer l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($data->email);
            $user->setFullName($data->fullName);
            $user->setPhone($data->phone);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Créer le paiement
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setAmount('10.00'); // Prix fixe du concert
        $payment->setPaymentMethod($data->paymentMethod);
        $payment->setStatus(Payment::STATUS_PENDING);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return new PaymentResponse(
            $payment->getId(),
            $payment->getStatus(),
            $payment->getAmount(),
            $payment->getPaymentMethod(),
            null,
            null,
            null,
            'Payment initiated successfully'
        );
    }

    public function confirmPayment(PaymentConfirmRequest $data): PaymentResponse
    {
        $payment = $this->entityManager->getRepository(Payment::class)->find($data->paymentId);

        if (!$payment || $payment->getStatus() !== Payment::STATUS_PENDING) {
            return new PaymentResponse(
                $data->paymentId,
                'error',
                '0.00',
                'unknown',
                null,
                null,
                null,
                'Payment not found or already processed'
            );
        }

        // Simulation de paiement réussi pour les tests
        // En production, intégrer FlexPay ici
        sleep(1); // Simuler un délai de traitement

        $payment->setStatus(Payment::STATUS_SUCCESS);
        $payment->setTransactionReference('TXN-' . uniqid());

        // Générer automatiquement un code d'accès après paiement réussi
        $accessCode = new \App\Entity\AccessCode();
        $accessCode->setUser($payment->getUser());
        $accessCode->setCode(\App\Entity\AccessCode::generateCode());
        $accessCode->setExpiresAt((new \DateTime())->modify('+24 hours'));

        $this->entityManager->persist($accessCode);
        $this->entityManager->flush();

        return new PaymentResponse(
            $payment->getId(),
            $payment->getStatus(),
            $payment->getAmount(),
            $payment->getPaymentMethod(),
            $payment->getTransactionReference(),
            'ORD-' . $payment->getId(),
            null,
            'Payment confirmed successfully. Access code generated.'
        );
    }
}