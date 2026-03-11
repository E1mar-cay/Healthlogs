<?php
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('h')) {
    function h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('require_login')) {
    function require_login(): void {
        $public = ['/HealthLogs/public/login.php', '/HealthLogs/public/auth.php'];
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        if (!isset($_SESSION['user_id']) && !in_array($path, $public, true)) {
            header('Location: /HealthLogs/public/login.php');
            exit;
        }
    }
}

require_login();

// Role-based access control (RBAC)
if (!function_exists('rbac_enforce')) {
    function rbac_enforce(): void {
        $role = $_SESSION['role'] ?? 'health_worker';
        if ($role === 'superadmin') {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

        // Public endpoints (already handled by require_login)
        $public = ['/HealthLogs/public/login.php', '/HealthLogs/public/auth.php', '/HealthLogs/public/logout.php'];
        if (in_array($path, $public, true)) {
            return;
        }

        $rules = [
            '/HealthLogs/public/forecast.php' => ['admin'],
            '/HealthLogs/public/forecast_run.php' => ['admin'],
            '/HealthLogs/public/reminders/run_cron.php' => ['admin'],
            '/HealthLogs/public/dashboards/superadmin.php' => ['superadmin'],
            '/HealthLogs/public/dashboards/admin.php' => ['admin'],
        ];

        foreach ($rules as $prefix => $allowed) {
            if (strncmp($path, $prefix, strlen($prefix)) === 0) {
                if (!in_array($role, $allowed, true)) {
                    http_response_code(403);
                    echo '<!doctype html><html><head><meta charset=\"utf-8\"><title>403</title></head>';
                    echo '<body style=\"font-family: sans-serif; padding: 40px;\">';
                    echo '<h1>403 Forbidden</h1><p>You do not have access to this page.</p>';
                    echo '<p><a href=\"/HealthLogs/public/index.php\">Back to dashboard</a></p>';
                    echo '</body></html>';
                    exit;
                }
                return;
            }
        }
    }
}

rbac_enforce();
