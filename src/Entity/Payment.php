<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
#[ApiResource(
    operations: [
        new \ApiPlatform\Metadata\GetCollection(
            security: "true",
            description: 'Lister tous les paiements'
        ),
        new \ApiPlatform\Metadata\Get(
            // security: "is_granted('ROLE_ADMIN')",
            description: 'Obtenir un paiement spécifique'
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/initiate',
            controller: \App\Controller\PaymentController::class,
            input: \App\DTO\PaymentInitiateRequest::class,
            output: \App\DTO\PaymentResponse::class,
            read: false,
            deserialize: true,
            validate: true,
            write: false,
            status: 201,
            description: 'Initier un paiement'
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/confirm',
            controller: \App\Controller\PaymentController::class,
            input: \App\DTO\PaymentConfirmRequest::class,
            output: \App\DTO\PaymentResponse::class,
            read: false,
            deserialize: true,
            validate: true,
            write: false,
            status: 200,
            description: 'Confirmer un paiement'
        )
    ],
    security: "is_granted('ROLE_ADMIN')",
    description: 'Entité représentant un paiement effectué par un utilisateur'
)]
class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public const PAYMENT_METHOD_CARD = 'card';
    public const PAYMENT_METHOD_MOBILE = 'mobile';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $amount;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_SUCCESS, self::STATUS_FAILED])]
    private string $status;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::PAYMENT_METHOD_CARD, self::PAYMENT_METHOD_MOBILE])]
    private string $paymentMethod;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $transactionReference = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->phoneNumber = null;
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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function setTransactionReference(?string $transactionReference): self
    {
        $this->transactionReference = $transactionReference;
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

    public static function getStatusChoices(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public static function getPaymentMethodChoices(): array
    {
        return [
            self::PAYMENT_METHOD_CARD => 'Credit Card',
            self::PAYMENT_METHOD_MOBILE => 'Mobile Payment',
        ];
    }
}