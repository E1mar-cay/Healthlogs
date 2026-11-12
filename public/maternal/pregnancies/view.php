<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    header("Location: /HealthLogs/public/maternal/tcl.php?pregnancy_id={$id}");
    exit;
}

header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
exit;
