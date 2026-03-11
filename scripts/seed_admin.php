<?php
require_once __DIR__ . '/../config/db.php';

// Seed admin role and user
$role = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch();
if (!$role) {
    $pdo->exec("INSERT INTO roles (name) VALUES ('admin')");
    $roleId = (int)$pdo->lastInsertId();
} else {
    $roleId = (int)$role['id'];
}

$user = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetch();
if (!$user) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role_id, username, password_hash, full_name, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$roleId, 'admin', $hash, 'System Admin', 'active']);
    echo "Created admin user (username: admin, password: admin123)\n";
} else {
    echo "Admin user already exists.\n";
}
