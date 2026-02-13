<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentConfirmRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $paymentId;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}