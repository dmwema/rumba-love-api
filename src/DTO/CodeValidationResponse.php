<?php

namespace App\DTO;

class CodeValidationResponse
{
    public string $token;
    public int $expiresIn = 300; // 5 minutes

    public function __construct(string $token)
    {
        $this->token = $token;
    }
}