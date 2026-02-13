<?php

namespace App\Controller;

use App\DTO\UserRegistrationRequest;
use App\DTO\UserResponse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UserController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(UserRegistrationRequest $data): UserResponse
    {
        // Vérifier si l'utilisateur existe déjà (par téléphone)
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['phone' => $data->phone]);

        if ($existingUser) {
            // Retourner l'utilisateur existant
            return new UserResponse(
                $existingUser->getId(),
                $existingUser->getFullName(),
                $existingUser->getEmail(),
                $existingUser->getPhone(),
                $existingUser->isCurrentlyOnline(),
                $existingUser->getLastActivity(),
                $existingUser->getCreatedAt()
            );
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setFullName($data->fullName);
        $user->setEmail($data->email);
        $user->setPhone($data->phone);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new UserResponse(
            $user->getId(),
            $user->getFullName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->isCurrentlyOnline(),
            $user->getLastActivity(),
            $user->getCreatedAt()
        );
    }
}