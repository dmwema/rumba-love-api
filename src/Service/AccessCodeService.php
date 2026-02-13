<?php

namespace App\Service;

use App\Entity\AccessCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AccessCodeService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function generateUniqueCode(): string
    {
        do {
            $code = AccessCode::generateCode();
            $existingCode = $this->entityManager->getRepository(AccessCode::class)->findOneBy(['code' => $code]);
        } while ($existingCode !== null);

        return $code;
    }

    public function createAccessCodeForUser(User $user): AccessCode
    {
        $accessCode = new AccessCode();
        $accessCode->setUser($user);
        $accessCode->setCode($this->generateUniqueCode());
        $accessCode->setExpiresAt((new \DateTime())->modify('+24 hours'));

        $this->entityManager->persist($accessCode);
        $this->entityManager->flush();

        return $accessCode;
    }

    public function validateCode(string $code): ?AccessCode
    {
        $accessCode = $this->entityManager->getRepository(AccessCode::class)->findOneBy(['code' => $code]);

        if (!$accessCode || !$accessCode->isValid()) {
            return null;
        }

        return $accessCode;
    }

    public function markCodeAsUsed(AccessCode $accessCode): void
    {
        $accessCode->markAsUsed();
        $this->entityManager->flush();
    }
}