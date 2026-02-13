<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationRequest
{
    #[Assert\NotBlank(message: 'Full name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Full name must be at least 2 characters')]
    public string $fullName;

    #[Assert\Email(message: 'Invalid email format')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Phone number is required')]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-\(\)]+$/', message: 'Invalid phone number format')]
    public string $phone;
}