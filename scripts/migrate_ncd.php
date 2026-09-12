<?php
require_once __DIR__ . '/../config/db.php';

echo "=== Running NCD Database Migration ===\n";

try {
    $sql = file_get_contents(__DIR__ . '/../schema/add_ncd_tables.sql');
    $pdo->exec($sql);
    echo "NCD tables created successfully!\n";
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
