<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessCodeService $accessCodeService
    ) {}

    public function initiatePayment(User $user, string $amount, string $paymentMethod): Payment
    {
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setAmount($amount);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setStatus(Payment::STATUS_PENDING);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    public function confirmPayment(int $paymentId): ?Payment
    {
        $payment = $this->entityManager->getRepository(Payment::class)->find($paymentId);

        if (!$payment || $payment->getStatus() !== Payment::STATUS_PENDING) {
            return null;
        }

        $payment->setStatus(Payment::STATUS_SUCCESS);
        $payment->setTransactionReference('TXN-' . uniqid());

        $this->entityManager->flush();

        // Générer automatiquement un code d'accès après paiement réussi
        $this->accessCodeService->createAccessCodeForUser($payment->getUser());

        return $payment;
    }

    public function failPayment(int $paymentId): ?Payment
    {
        $payment = $this->entityManager->getRepository(Payment::class)->find($paymentId);

        if (!$payment || $payment->getStatus() !== Payment::STATUS_PENDING) {
            return null;
        }

        $payment->setStatus(Payment::STATUS_FAILED);
        $this->entityManager->flush();

        return $payment;
    }
}