<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use App\Entity\Payment;

// Définir les variables d'environnement
putenv('DATABASE_URL=sqlite:///' . __DIR__ . '/var/data.db');
putenv('APP_ENV=dev');
putenv('APP_SECRET=dev_secret_key');
$_ENV['DATABASE_URL'] = 'sqlite:///' . __DIR__ . '/var/data.db';
$_ENV['APP_ENV'] = 'dev';
$_ENV['APP_SECRET'] = 'dev_secret_key';

try {
    $kernel = new Kernel('dev', true);
    $kernel->boot();

    $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

    echo "✓ Doctrine EntityManager loaded successfully\n";

    // Créer un utilisateur de test
    $user = new User();
    $user->setEmail('test-phone-' . time() . '@example.com');
    $user->setFullName('Test Phone User');
    $user->setPhone('243123456789');
    $user->setCreatedAt(new \DateTime());
    $user->setIsOnline(false);

    $entityManager->persist($user);

    // Créer un paiement avec phoneNumber
    $payment = new Payment();
    $payment->setUser($user);
    $payment->setAmount('5.00');
    $payment->setPaymentMethod('mobile');
    $payment->setPhoneNumber('243123456789');
    $payment->setStatus('pending');
    $payment->setCreatedAt(new \DateTime());

    echo "Payment phoneNumber: " . $payment->getPhoneNumber() . "\n";

    $entityManager->persist($payment);
    $entityManager->flush();

    echo "✓ User created with ID: " . $user->getId() . "\n";
    echo "✓ Payment created with ID: " . $payment->getId() . " and phoneNumber: " . $payment->getPhoneNumber() . "\n";

    echo "✓ Doctrine operations successful!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}