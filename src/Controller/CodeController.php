<?php

namespace App\Controller;

use App\DTO\CodeValidationRequest;
use App\DTO\CodeValidationResponse;
use App\Entity\AccessCode;
use App\Service\LiveAccessTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class CodeController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LiveAccessTokenService $liveAccessTokenService
    ) {}

    public function validateCode(CodeValidationRequest $data): CodeValidationResponse
    {
        $accessCode = $this->entityManager->getRepository(AccessCode::class)->findOneBy(['code' => $data->code]);

        if (!$accessCode || !$accessCode->isValid()) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid or expired access code');
        }

        // Marquer le code comme utilisé
        $accessCode->markAsUsed();
        $this->entityManager->flush();

        // Générer le token d'accès live temporaire
        $token = $this->liveAccessTokenService->generateLiveAccessToken(
            $accessCode->getUser()->getId(),
            $accessCode->getCode()
        );

        // Marquer l'utilisateur comme en ligne
        $user = $accessCode->getUser();
        $user->setIsOnline(true);
        $this->entityManager->flush();

        return new CodeValidationResponse($token);
    }
}