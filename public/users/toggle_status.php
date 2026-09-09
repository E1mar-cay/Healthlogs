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

// Prevent users from disabling themselves
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot disable your own account';
    header('Location: /HealthLogs/public/users.php');
    exit;
}

try {
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT id, username, status FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found';
        header('Location: /HealthLogs/public/users.php');
        exit;
    }
    
    // Determine new status
    $targetStatus = $_POST['status'] ?? '';
    if (!in_array($targetStatus, ['active', 'inactive'], true)) {
        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
    } else {
        $newStatus = $targetStatus;
    }
    
    // Update user status
    $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $userId]);
    
    if ($newStatus === 'active') {
        $_SESSION['success_message'] = "User '{$user['username']}' has been enabled successfully.";
    } else {
        $_SESSION['success_message'] = "User '{$user['username']}' has been disabled.";
    }
    
} catch (Exception $e) {
    error_log("User toggle status error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while updating user status. Please try again.';
}

header('Location: /HealthLogs/public/users.php');
exit;
