<?php
if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../../app/Core/EnvLoader.php';
EnvLoader::load(__DIR__ . '/../../.env');

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Core/Validator.php';
require_once __DIR__ . '/../../app/Core/FlashHelper.php';
require_once __DIR__ . '/../../app/Core/Paginator.php';
require_once __DIR__ . '/../../app/Core/ActivityLogger.php';
ActivityLogger::init($pdo);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('h')) {
    function h($value): string {
        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return htmlspecialchars($encoded !== false ? $encoded : '[invalid value]', ENT_QUOTES, 'UTF-8');
        }
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

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        $role = strtolower(trim((string)($_SESSION['role'] ?? 'health_worker')));
        $role = str_replace(['-', ' '], '_', $role);
        return in_array($role, ['admin', 'administrator', 'superadmin'], true);
    }
}

if (!function_exists('can_manage_clinical_records')) {
    function can_manage_clinical_records(): bool {
        return !is_admin();
    }
}

// Role-based access control (RBAC)
if (!function_exists('rbac_enforce')) {
    function rbac_enforce(): void {
        $role = strtolower(trim((string)($_SESSION['role'] ?? 'health_worker')));
        $role = str_replace(['-', ' '], '_', $role);
        if (in_array($role, ['administrator', 'superadmin'], true)) {
            $role = 'admin';
        }
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

        // Public endpoints (already handled by require_login)
        $public = ['/HealthLogs/public/login.php', '/HealthLogs/public/auth.php', '/HealthLogs/public/logout.php'];
        if (in_array($path, $public, true)) {
            return;
        }

        // Exact & Prefix RBAC Access Rules
        // Admin-only: Reports, Forecasts, User Management, Admin Dashboard
        // Health Worker-only: Clinical creation, forms, saving, and deletion across all health programs
        $rules = [
            // Admin only features
            '/HealthLogs/public/reports.php' => ['admin'],
            '/HealthLogs/public/forecast.php' => ['admin'],
            '/HealthLogs/public/forecast_run.php' => ['admin'],
            '/HealthLogs/public/forecast_run_details.php' => ['admin'],
            '/HealthLogs/public/dashboards/admin.php' => ['admin'],
            '/HealthLogs/public/users.php' => ['admin'],
            '/HealthLogs/public/users/' => ['admin'],
            '/HealthLogs/public/activity_logs.php' => ['admin'],

            // Clinical / Patient management (Admin is restricted to Monitoring & Oversight only)
            '/HealthLogs/public/patients/form.php' => ['health_worker'],
            '/HealthLogs/public/patients/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/patients/save.php' => ['health_worker'],
            '/HealthLogs/public/patients/delete.php' => ['health_worker'],

            '/HealthLogs/public/immunization/records/form.php' => ['health_worker'],
            '/HealthLogs/public/immunization/records/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/immunization/records/save.php' => ['health_worker'],
            '/HealthLogs/public/immunization/records/delete.php' => ['health_worker'],
            '/HealthLogs/public/immunization/schedules/form.php' => ['health_worker'],
            '/HealthLogs/public/immunization/schedules/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/immunization/schedules/save.php' => ['health_worker'],
            '/HealthLogs/public/immunization/schedules/delete.php' => ['health_worker'],
            '/HealthLogs/public/immunization/vaccines/form.php' => ['health_worker'],
            '/HealthLogs/public/immunization/vaccines/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/immunization/vaccines/save.php' => ['health_worker'],
            '/HealthLogs/public/immunization/vaccines/delete.php' => ['health_worker'],

            '/HealthLogs/public/maternal/pregnancies/form.php' => ['health_worker'],
            '/HealthLogs/public/maternal/pregnancies/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/maternal/pregnancies/save.php' => ['health_worker'],
            '/HealthLogs/public/maternal/pregnancies/delete.php' => ['health_worker'],
            '/HealthLogs/public/maternal/prenatal/form.php' => ['health_worker'],
            '/HealthLogs/public/maternal/prenatal/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/maternal/prenatal/save.php' => ['health_worker'],
            '/HealthLogs/public/maternal/prenatal/delete.php' => ['health_worker'],
            '/HealthLogs/public/maternal/postnatal/form.php' => ['health_worker'],
            '/HealthLogs/public/maternal/postnatal/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/maternal/postnatal/save.php' => ['health_worker'],
            '/HealthLogs/public/maternal/postnatal/delete.php' => ['health_worker'],

            '/HealthLogs/public/family_planning/records/create.php' => ['health_worker'],
            '/HealthLogs/public/family_planning/records/save.php' => ['health_worker'],
            '/HealthLogs/public/family_planning/records/delete.php' => ['health_worker'],

            '/HealthLogs/public/ncd/records/create.php' => ['health_worker'],
            '/HealthLogs/public/ncd/records/save.php' => ['health_worker'],
            '/HealthLogs/public/ncd/records/delete.php' => ['health_worker'],

            '/HealthLogs/public/inventory/medicines/form.php' => ['health_worker'],
            '/HealthLogs/public/inventory/medicines/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/inventory/medicines/save.php' => ['health_worker'],
            '/HealthLogs/public/inventory/medicines/delete.php' => ['health_worker'],
            '/HealthLogs/public/inventory/batches/form.php' => ['health_worker'],
            '/HealthLogs/public/inventory/batches/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/inventory/batches/save.php' => ['health_worker'],
            '/HealthLogs/public/inventory/batches/delete.php' => ['health_worker'],
            '/HealthLogs/public/inventory/transactions/form.php' => ['health_worker'],
            '/HealthLogs/public/inventory/transactions/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/inventory/transactions/save.php' => ['health_worker'],
            '/HealthLogs/public/inventory/transactions/delete.php' => ['health_worker'],

            '/HealthLogs/public/reminders/form.php' => ['health_worker'],
            '/HealthLogs/public/reminders/form_embed.php' => ['health_worker'],
            '/HealthLogs/public/reminders/save.php' => ['health_worker'],
            '/HealthLogs/public/reminders/delete.php' => ['health_worker'],
            '/HealthLogs/public/reminders/run_cron.php' => ['admin', 'health_worker'],
        ];

        foreach ($rules as $prefix => $allowed) {
            if (strncmp($path, $prefix, strlen($prefix)) === 0) {
                if (!in_array($role, $allowed, true)) {
                    http_response_code(403);
                    echo '<!doctype html><html><head><meta charset="utf-8"><title>403 Forbidden - HealthLogs</title>';
                    echo '<link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">';
                    echo '<script src="/HealthLogs/public/assets/js/tailwind.js"></script></head>';
                    echo '<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 font-sans text-slate-900">';
                    echo '<div class="bg-white border border-slate-200 rounded-2xl shadow-xl max-w-md w-full p-6 text-center">';
                    echo '<div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto text-xl mb-4 font-bold">403</div>';
                    echo '<h1 class="text-xl font-bold text-slate-900 mb-2">Access Restricted</h1>';
                    if ($role === 'admin') {
                        echo '<p class="text-xs text-slate-600 mb-6">Administrators have monitoring, reporting, and oversight privileges only. Patient intake, clinical encounters, and record modifications are strictly reserved for Barangay Health Workers (BHW).</p>';
                    } else {
                        echo '<p class="text-xs text-slate-600 mb-6">You do not have permission to access this administrative feature.</p>';
                    }
                    echo '<a href="/HealthLogs/public/index.php" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">Return to Dashboard</a>';
                    echo '</div></body></html>';
                    exit;
                }
                return;
            }
        }
    }
}

rbac_enforce();
