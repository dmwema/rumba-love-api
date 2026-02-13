<?php

// Vérifier les données persistées dans la base
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== UTILISATEURS ===\n";
    $stmt = $pdo->query("SELECT * FROM user ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Email: {$row['email']}, Name: {$row['full_name']}, Phone: {$row['phone']}\n";
    }

    echo "\n=== PAIEMENTS ===\n";
    $stmt = $pdo->query("SELECT * FROM payment ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, UserID: {$row['user_id']}, Amount: {$row['amount']}, Status: {$row['status']}, Method: {$row['payment_method']}, Ref: {$row['transaction_reference']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}