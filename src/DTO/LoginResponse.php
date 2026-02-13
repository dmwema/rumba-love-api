<?php

namespace App\DTO;

class LoginResponse
{
    public string $token;
    public UserResponse $user;
    public string $message;
    public int $expiresIn;

    public function __construct(string $token, UserResponse $user, string $message = 'Authentication successful', int $expiresIn = 3600)
    {
        $this->token = $token;
        $this->user = $user;
        $this->message = $message;
        $this->expiresIn = $expiresIn;
    }
}