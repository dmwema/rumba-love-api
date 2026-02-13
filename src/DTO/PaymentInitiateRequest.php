<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentInitiateRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $fullName;

    #[Assert\Regex(pattern: '/^\+?[0-9\s\-\(\)]+$/', message: 'Invalid phone number format')]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['card', 'mobile'])]
    public string $paymentMethod;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}