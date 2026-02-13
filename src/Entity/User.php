<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ApiResource(
    operations: [
        new \ApiPlatform\Metadata\GetCollection(
            security: "true",
            description: 'Lister tous les utilisateurs avec leur statut en ligne'
        ),
        new \ApiPlatform\Metadata\Get(
            // security: "is_granted('ROLE_ADMIN')",
            description: 'Obtenir un utilisateur spécifique'
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/register',
            controller: \App\Controller\UserController::class,
            input: \App\DTO\UserRegistrationRequest::class,
            output: \App\DTO\UserResponse::class,
            read: false,
            deserialize: true,
            validate: true,
            write: false,
            status: 201,
            description: 'Enregistrer un nouvel utilisateur'
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/login',
            controller: \App\Controller\AuthController::class,
            input: \App\DTO\LoginRequest::class,
            output: \App\DTO\LoginResponse::class,
            read: false,
            deserialize: true,
            validate: true,
            write: false,
            status: 200,
            description: 'Authentifier un utilisateur'
        )
    ],
    security: "is_granted('ROLE_ADMIN')",
    description: 'Entité représentant un utilisateur du système de live streaming'
)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-\(\)]+$/', message: 'Invalid phone number format')]
    private ?string $phone = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastActivity = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isOnline = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $payments;

    /**
     * @var Collection<int, AccessCode>
     */
    #[ORM\OneToMany(targetEntity: AccessCode::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $accessCodes;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->payments = new ArrayCollection();
        $this->accessCodes = new ArrayCollection();
        $this->isOnline = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
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

    public function getLastActivity(): ?\DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeInterface $lastActivity): self
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): self
    {
        $this->isOnline = $isOnline;
        $this->lastActivity = $isOnline ? new \DateTime() : $this->lastActivity;
        return $this;
    }

    public function isCurrentlyOnline(): bool
    {
        if (!$this->lastActivity) {
            return false;
        }

        // Considérer en ligne si activité dans les dernières 5 minutes
        $fiveMinutesAgo = new \DateTime('-5 minutes');
        return $this->lastActivity > $fiveMinutesAgo;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getUser() === $this) {
                $payment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccessCode>
     */
    public function getAccessCodes(): Collection
    {
        return $this->accessCodes;
    }

    public function addAccessCode(AccessCode $accessCode): self
    {
        if (!$this->accessCodes->contains($accessCode)) {
            $this->accessCodes->add($accessCode);
            $accessCode->setUser($this);
        }

        return $this;
    }

    public function removeAccessCode(AccessCode $accessCode): self
    {
        if ($this->accessCodes->removeElement($accessCode)) {
            // set the owning side to null (unless already changed)
            if ($accessCode->getUser() === $this) {
                $accessCode->setUser(null);
            }
        }

        return $this;
    }
}