<?php
require_once __DIR__ . '/../config/db.php';

echo "Testing User Management Functionality\n";
echo "====================================\n\n";

// Check if users exist
$users = $pdo->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id")->fetchAll();

echo "Users in database:\n";
foreach ($users as $user) {
    echo "  ID: {$user['id']} | Username: {$user['username']} | Role: {$user['role_name']} | Status: {$user['status']}\n";
}

echo "\nRoles in database:\n";
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
foreach ($roles as $role) {
    echo "  ID: {$role['id']} | Name: {$role['name']}\n";
}

echo "\nChecking file permissions:\n";
$files = [
    'users.php',
    'users/form.php', 
    'users/save.php',
    'users/delete.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../public/' . $file;
    if (file_exists($path)) {
        echo "  ✓ $file exists\n";
    } else {
        echo "  ✗ $file missing\n";
    }
}

echo "\nUser management test completed!\n";
