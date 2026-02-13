<?php

// Script pour créer la base de données SQLite manuellement
try {
    $dbPath = __DIR__ . '/var/data.db';

    // Créer le répertoire var s'il n'existe pas
    if (!is_dir(__DIR__ . '/var')) {
        mkdir(__DIR__ . '/var', 0777, true);
    }

    // Créer la base de données SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database created successfully at: $dbPath\n";

    // Créer les tables Doctrine (noms au pluriel)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(180) UNIQUE,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            last_activity DATETIME,
            is_online BOOLEAN DEFAULT 0,
            created_at DATETIME NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            amount VARCHAR(10) NOT NULL,
            status VARCHAR(20) NOT NULL,
            payment_method VARCHAR(20) NOT NULL,
            transaction_reference VARCHAR(255),
            created_at DATETIME NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS access_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            code VARCHAR(20) UNIQUE NOT NULL,
            is_used BOOLEAN DEFAULT 0,
            used_at DATETIME,
            expires_at DATETIME,
            created_at DATETIME NOT NULL
        )
    ");

    echo "Tables created successfully\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}