<?php
require __DIR__ . '/partials/bootstrap.php';

$role = $_SESSION['role'] ?? 'health_worker';

switch ($role) {
    case 'superadmin':
        require __DIR__ . '/dashboards/superadmin.php';
        break;
    case 'admin':
        require __DIR__ . '/dashboards/admin.php';
        break;
    default:
        require __DIR__ . '/dashboards/health_worker.php';
        break;
}
