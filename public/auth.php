<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/Recaptcha.php';

session_start();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (Recaptcha::isConfigured()) {
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!Recaptcha::verifyResponse($captchaResponse, $remoteIp)) {
        header('Location: /HealthLogs/public/login.php?error=captcha');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.password_hash, u.status, r.name AS role_name
    FROM users u
    JOIN roles r ON r.id = u.role_id
    WHERE u.username = ?
    LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
    header('Location: /HealthLogs/public/login.php?error=1');
    exit;
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role_name'];

header('Location: /HealthLogs/public/index.php');
exit;
