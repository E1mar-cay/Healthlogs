<?php
require_once __DIR__ . '/../config/db.php';

echo "Removing superadmin role\n";
echo "========================\n\n";

try {
    $pdo->beginTransaction();

    $adminRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
    if (!$adminRoleId) {
        $stmt = $pdo->prepare("INSERT INTO roles (name) VALUES ('admin')");
        $stmt->execute();
        $adminRoleId = (int)$pdo->lastInsertId();
        echo "Created missing admin role.\n";
    } else {
        $adminRoleId = (int)$adminRoleId;
    }

    $superadminRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'superadmin' LIMIT 1")->fetchColumn();
    if (!$superadminRoleId) {
        $pdo->commit();
        echo "No superadmin role found. Nothing to do.\n";
        exit(0);
    }

    $superadminRoleId = (int)$superadminRoleId;

    $reassignStmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE role_id = ?");
    $reassignStmt->execute([$adminRoleId, $superadminRoleId]);
    $reassignedUsers = $reassignStmt->rowCount();

    $deleteRoleStmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $deleteRoleStmt->execute([$superadminRoleId]);

    $pdo->commit();

    echo "Reassigned {$reassignedUsers} superadmin user(s) to admin.\n";
    echo "Deleted superadmin role.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Failed: " . $e->getMessage() . "\n");
    exit(1);
}
