<?php

// Vérifier les données Doctrine persistées
$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');

echo "=== UTILISATEURS ===\n";
$stmt = $pdo->query('SELECT id, email, full_name FROM users ORDER BY id DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Email: {$row['email']}, Name: {$row['full_name']}\n";
}

echo "\n=== PAIEMENTS ===\n";
$stmt = $pdo->query('SELECT id, user_id, status, payment_method, transaction_reference FROM payments ORDER BY id DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, UserID: {$row['user_id']}, Status: {$row['status']}, Method: {$row['payment_method']}, Ref: {$row['transaction_reference']}\n";
}