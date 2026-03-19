<?php
require_once __DIR__ . '/../config/db.php';

echo "Testing Dashboard Updates\n";
echo "========================\n\n";

// Check if users have full names
$users = $pdo->query("SELECT id, username, full_name, status FROM users WHERE status = 'active'")->fetchAll();

echo "Active users with full names:\n";
foreach ($users as $user) {
    $fullName = $user['full_name'] ?: 'No full name';
    echo "  ID: {$user['id']} | Username: {$user['username']} | Full Name: {$fullName}\n";
}

echo "\nDashboard files updated:\n";
$dashboards = [
    'health_worker.php' => 'Health Worker Dashboard',
    'admin.php' => 'Administrator Dashboard', 
    'superadmin.php' => 'Superadmin Dashboard'
];

foreach ($dashboards as $file => $title) {
    $path = __DIR__ . '/../public/dashboards/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, 'Welcome back,') !== false) {
            echo "  ✓ $title - Welcome message added\n";
        } else {
            echo "  ✗ $title - Welcome message missing\n";
        }
        
        if ($file === 'health_worker.php' && strpos($content, 'Medicine') !== false) {
            echo "  ✓ Health Worker - Medicine card added\n";
        }
    } else {
        echo "  ✗ $file not found\n";
    }
}

echo "\nAuth.php updated:\n";
$authPath = __DIR__ . '/../public/auth.php';
if (file_exists($authPath)) {
    $authContent = file_get_contents($authPath);
    if (strpos($authContent, 'full_name') !== false) {
        echo "  ✓ Auth process stores full_name in session\n";
    } else {
        echo "  ✗ Auth process missing full_name\n";
    }
}

echo "\nDashboard update test completed!\n";