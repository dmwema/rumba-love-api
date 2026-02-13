<?php

namespace App\DTO;

class PaymentResponse
{
    public int $paymentId;
    public string $status;
    public string $amount;
    public string $paymentMethod;
    public ?string $transactionReference;
    public ?string $orderNumber;
    public ?string $redirectUrl;
    public string $message;

    public function __construct(
        int $paymentId,
        string $status,
        string $amount,
        string $paymentMethod,
        ?string $transactionReference = null,
        ?string $orderNumber = null,
        ?string $redirectUrl = null,
        string $message = ''
    ) {
        $this->paymentId = $paymentId;
        $this->status = $status;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        $this->transactionReference = $transactionReference;
        $this->orderNumber = $orderNumber;
        $this->redirectUrl = $redirectUrl;
        $this->message = $message;
    }
}