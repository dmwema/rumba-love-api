<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SecureStreamAccessRequest
{
    #[Assert\NotBlank(message: 'Live token is required')]
    public string $liveToken;

    #[Assert\NotBlank(message: 'User ID is required')]
    #[Assert\Positive(message: 'User ID must be positive')]
    public int $userId;

    #[Assert\NotBlank(message: 'Session ID is required')]
    public string $sessionId;
}