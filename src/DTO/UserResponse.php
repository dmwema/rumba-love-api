<?php

namespace App\DTO;

class UserResponse
{
    public int $id;
    public string $fullName;
    public ?string $email;
    public string $phone;
    public bool $isOnline;
    public ?string $lastActivity;
    public string $createdAt;

    public function __construct(
        int $id,
        string $fullName,
        ?string $email,
        string $phone,
        bool $isOnline,
        ?\DateTimeInterface $lastActivity,
        \DateTimeInterface $createdAt
    ) {
        $this->id = $id;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->phone = $phone;
        $this->isOnline = $isOnline;
        $this->lastActivity = $lastActivity?->format('c');
        $this->createdAt = $createdAt->format('c');
    }
}