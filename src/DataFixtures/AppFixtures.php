<?php

namespace App\DataFixtures;

use App\Entity\AdminUser;
use App\Entity\LiveEvent;
use App\Service\StreamUrlEncryptionService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private StreamUrlEncryptionService $encryptionService
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new AdminUser();
        $admin->setEmail('cinefilm.cd');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'p@ssword123654');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Create default live event
        $liveEvent = new LiveEvent();
        $liveEvent->setTitle('Concert Live - Artiste Mystère');
        $liveEvent->setDescription('Un concert exceptionnel en direct. Réservez votre place dès maintenant !');
        $liveEvent->setImageUrl('https://example.com/concert-image.jpg');
        $liveEvent->setPrice('10.00');
        // Encrypt a placeholder URL - this will be updated later by admin
        $encryptedUrl = $this->encryptionService->encrypt('https://example.com/live-stream-url');
        $liveEvent->setStreamUrl($encryptedUrl);
        $liveEvent->setIsActive(false);
        $liveEvent->setLiveDate(new \DateTime('2026-02-15 20:00:00')); // Samedi prochain
        $manager->persist($liveEvent);

        $manager->flush();
    }
}