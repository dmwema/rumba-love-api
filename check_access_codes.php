<?php

// Vérifier les access codes générés
$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');

echo "=== ACCESS CODES ===\n";
$stmt = $pdo->query('SELECT id, user_id, code, expires_at, is_used FROM access_codes ORDER BY id DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, UserID: {$row['user_id']}, Code: {$row['code']}, Expires: {$row['expires_at']}, Used: {$row['is_used']}\n";
}