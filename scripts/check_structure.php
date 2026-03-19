<?php
require_once __DIR__ . '/../config/db.php';

echo "Checking database structure...\n\n";

// Check patients table structure
echo "PATIENTS TABLE:\n";
$stmt = $pdo->query("DESCRIBE patients");
while ($row = $stmt->fetch()) {
    echo "  {$row['Field']} - {$row['Type']}\n";
}

echo "\nHOUSEHOLDS TABLE:\n";
$stmt = $pdo->query("DESCRIBE households");
while ($row = $stmt->fetch()) {
    echo "  {$row['Field']} - {$row['Type']}\n";
}