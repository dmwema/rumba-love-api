<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CodeValidationRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 12)]
    #[Assert\Regex(pattern: '/^[A-Z0-9\-]+$/', message: 'Code must contain only uppercase letters, numbers and dashes')]
    public string $code;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}