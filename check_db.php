<?php

// Vérifier la configuration de la base de données
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ?: 'Not set') . "\n";

// Essayer de se connecter à la base de données
try {
    $dsn = getenv('DATABASE_URL') ?: 'sqlite:///%kernel.project_dir%/var/data.db';
    echo "DSN: $dsn\n";

    if (strpos($dsn, 'sqlite:') === 0) {
        $path = str_replace('sqlite:///', '', $dsn);
        $path = str_replace('%kernel.project_dir%', __DIR__, $path);
        echo "SQLite file path: $path\n";

        if (file_exists($path)) {
            echo "SQLite file exists\n";
            $pdo = new PDO($dsn);
            echo "PDO connection successful\n";
        } else {
            echo "SQLite file does not exist\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Lister les extensions PDO disponibles
echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";