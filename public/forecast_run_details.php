<?php
require_once __DIR__ . '/partials/bootstrap.php';
require_once __DIR__ . '/../app/Core/ForecastLogger.php';

header('Content-Type: application/json');

$runId = (int)($_GET['run_id'] ?? 0);
if ($runId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid run ID']);
    exit;
}

$details = ForecastLogger::getRunDetails($runId);
if (!$details) {
    http_response_code(404);
    echo json_encode(['error' => 'Run not found']);
    exit;
}

echo json_encode($details);
