<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/var/data.db');
$pdo->exec('DROP TABLE IF EXISTS payments');
$pdo->exec('
    CREATE TABLE payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        amount VARCHAR(10) NOT NULL,
        status VARCHAR(20) NOT NULL,
        payment_method VARCHAR(20) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        transaction_reference VARCHAR(255),
        created_at DATETIME NOT NULL
    )
');
echo 'Table payments recréée avec phone_number\n';