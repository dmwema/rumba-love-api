<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');
$stmt = $pdo->query('SELECT id, code, LENGTH(code) as length FROM access_codes ORDER BY id DESC LIMIT 3');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo 'Access code - ID: ' . $row['id'] . ', Code: ' . $row['code'] . ', Longueur: ' . $row['length'] . PHP_EOL;
}