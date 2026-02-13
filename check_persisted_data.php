<?php

// Vérifier les données persistées
$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');

echo "=== UTILISATEURS ===\n";
$stmt = $pdo->query('SELECT * FROM users ORDER BY id DESC LIMIT 3');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Email: {$row['email']}, Name: {$row['full_name']}, Phone: {$row['phone']}\n";
}

echo "\n=== PAIEMENTS ===\n";
$stmt = $pdo->query('SELECT * FROM payments ORDER BY id DESC LIMIT 3');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, UserID: {$row['user_id']}, Amount: {$row['amount']}, Status: {$row['status']}, Method: {$row['payment_method']}, Ref: {$row['transaction_reference']}\n";
}