<?php
require __DIR__ . '/../partials/bootstrap.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /HealthLogs/public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: /HealthLogs/public/users.php');
    exit;
}

$userId = (int)$_POST['id'];

// Prevent users from deleting themselves
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot delete your own account';
    header('Location: /HealthLogs/public/users.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = 'User not found';
        header('Location: /HealthLogs/public/users.php');
        exit;
    }
    
    // Delete the user
    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->execute([$userId]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "User '{$user['username']}' deleted successfully";
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("User delete error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while deleting the user. Please try again.';
}

header('Location: /HealthLogs/public/users.php');
exit;
