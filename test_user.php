<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;

putenv('DATABASE_URL=sqlite:///' . __DIR__ . '/var/data.db');
putenv('APP_ENV=dev');
putenv('APP_SECRET=dev_secret_key');

try {
    $kernel = new Kernel('dev', true);
    $kernel->boot();

    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

    echo "✓ Doctrine loaded\n";

    $user = new User();
    $user->setEmail('test-user-' . time() . '@example.com');
    $user->setFullName('Test User');
    $user->setCreatedAt(new \DateTime());
    $user->setIsOnline(false);

    $entityManager->persist($user);
    $entityManager->flush();

    echo "✓ User created with ID: " . $user->getId() . "\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}