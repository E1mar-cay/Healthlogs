<?php
require_once __DIR__ . '/../config/db.php';

// Ensure roles exist
$roles = ['admin', 'health_worker'];
$roleIds = [];
foreach ($roles as $r) {
    $row = $pdo->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
    $row->execute([$r]);
    $found = $row->fetch();
    if ($found) {
        $roleIds[$r] = (int)$found['id'];
    } else {
        $ins = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
        $ins->execute([$r]);
        $roleIds[$r] = (int)$pdo->lastInsertId();
    }
}

function upsert_user(PDO $pdo, int $roleId, string $username, string $password, string $fullName): void {
    $row = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $row->execute([$username]);
    $found = $row->fetch();
    if ($found) {
        echo "User {$username} already exists.\n";
        return;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role_id, username, password_hash, full_name, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$roleId, $username, $hash, $fullName, 'active']);
    echo "Created {$username} ({$fullName})\n";
}

upsert_user($pdo, $roleIds['admin'], 'admin', 'admin123', 'Barangay Health Administrator');
upsert_user($pdo, $roleIds['health_worker'], 'bhw', 'bhw123', 'Barangay Health Worker');
