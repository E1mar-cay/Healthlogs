<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/ActivityLogger.php';
ActivityLogger::init($pdo);

session_start();

if (!empty($_SESSION['username'])) {
    ActivityLogger::logAuth('logout', $_SESSION['username'], true, [
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null
    ]);
}

session_unset();
session_destroy();

header('Location: /HealthLogs/public/login.php');
exit;
