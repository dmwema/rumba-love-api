<?php

// Test de connexion à la base de données
require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;

try {
    $kernel = new Kernel('dev', true);
    $kernel->boot();

    $entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);

    echo "Doctrine EntityManager created successfully\n";

    // Tester une requête simple
    $connection = $entityManager->getConnection();
    $result = $connection->executeQuery('SELECT 1 as test')->fetchOne();

    echo "Database connection test successful: $result\n";

    // Tester la création d'un utilisateur
    $user = new \App\Entity\User();
    $user->setEmail('test' . time() . '@example.com');
    $user->setFullName('Test User');
    $user->setPhone('243123456789');
    $user->setCreatedAt(new \DateTimeImmutable());

    $entityManager->persist($user);
    $entityManager->flush();

    echo "User created successfully with ID: " . $user->getId() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}