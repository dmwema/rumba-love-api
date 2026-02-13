<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'access_codes')]
#[ORM\Index(columns: ['code'], name: 'code_idx')]
#[ApiResource(
    operations: [
        new \ApiPlatform\Metadata\GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            description: 'Lister tous les codes d\'accès'
        ),
        new \ApiPlatform\Metadata\Get(
            security: "is_granted('ROLE_ADMIN')",
            description: 'Obtenir un code d\'accès spécifique'
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/validate',
            controller: \App\Controller\CodeController::class,
            input: \App\DTO\CodeValidationRequest::class,
            output: \App\DTO\CodeValidationResponse::class,
            read: false,
            deserialize: true,
            validate: true,
            write: false,
            status: 200,
            description: 'Valider un code d\'accès et obtenir un token live'
        )
    ],
    security: "is_granted('ROLE_ADMIN')",
    description: 'Entité représentant un code d\'accès unique pour accéder au live streaming'
)]
class AccessCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 15, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 15)]
    #[Assert\Regex(pattern: '/^[A-Z0-9\-]+$/', message: 'Code must contain only uppercase letters, numbers and dashes')]
    private string $code;

    #[ORM\Column(type: 'boolean')]
    private bool $isUsed = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $usedAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    private \DateTime $expiresAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->expiresAt = (new \DateTime())->modify('+24 hours');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(bool $isUsed): self
    {
        $this->isUsed = $isUsed;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function isValid(): bool
    {
        return !$this->isUsed && !$this->isExpired();
    }

    public function markAsUsed(): self
    {
        $this->isUsed = true;
        $this->usedAt = new \DateTime();
        return $this;
    }

    public static function generateCode(): string
    {
        $prefix = 'CINE-';
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = $prefix;

        // Génère exactement 7 caractères pour faire 12 au total ('CINE-' = 5 + 7 = 12)
        for ($i = 0; $i < 7; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $code;
    }
}