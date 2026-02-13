<?php

// Vérifier la mise à jour du statut
$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');
$stmt = $pdo->query('SELECT id, status FROM payments WHERE id = 13');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'Paiement ID 13 - Statut: ' . $row['status'] . PHP_EOL;