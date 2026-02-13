<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'live_events')]
#[ApiResource(
    operations: [],
    // security: "is_granted('ROLE_ADMIN')",
    description: 'Entité représentant un événement de live streaming'
)]
class LiveEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    private string $description;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $price;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $streamUrl;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    private \DateTimeInterface $liveDate;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->price = '10.00';
        $this->liveDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getStreamUrl(): string
    {
        return $this->streamUrl;
    }

    public function setStreamUrl(string $streamUrl): self
    {
        $this->streamUrl = $streamUrl;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLiveDate(): \DateTimeInterface
    {
        return $this->liveDate;
    }

    public function setLiveDate(\DateTimeInterface $liveDate): self
    {
        $this->liveDate = $liveDate;
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

    public function isLiveNow(): bool
    {
        $now = new \DateTime();
        return $this->isActive && $this->liveDate <= $now;
    }
}