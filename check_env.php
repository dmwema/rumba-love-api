<?php

// Définir les variables d'environnement manuellement
putenv('DATABASE_URL=sqlite:///' . __DIR__ . '/var/data.db');
putenv('APP_ENV=dev');
putenv('APP_SECRET=dev_secret_key');

// Vérifier la configuration de l'environnement
echo "=== ENVIRONMENT CONFIGURATION ===\n";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ?: 'Not set') . "\n";
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'Not set') . "\n";
echo "APP_SECRET: " . (substr(getenv('APP_SECRET') ?: '', 0, 10) . '...') . "\n";

// Tester la connexion Doctrine
echo "\n=== DOCTRINE CONNECTION TEST ===\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';

    $kernel = new App\Kernel('dev', true);
    $kernel->boot();

    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    echo "✓ Doctrine EntityManager loaded successfully\n";

    $connection = $entityManager->getConnection();
    echo "✓ Database connection established\n";

    // Tester une requête simple
    $result = $connection->executeQuery('SELECT 1 as test')->fetchOne();
    echo "✓ Database query successful: $result\n";

    // Tester les repositories
    $userRepo = $entityManager->getRepository(App\Entity\User::class);
    $paymentRepo = $entityManager->getRepository(App\Entity\Payment::class);
    echo "✓ Repositories loaded successfully\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}