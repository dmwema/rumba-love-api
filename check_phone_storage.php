<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');
$stmt = $pdo->query('SELECT id, phone_number FROM payments ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'Dernier paiement - ID: ' . $row['id'] . ', Phone: ' . $row['phone_number'] . PHP_EOL;