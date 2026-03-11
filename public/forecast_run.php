<?php
require __DIR__ . '/partials/bootstrap.php';

$seriesKey = $_POST['series_key'] ?? 'visits_total';
$horizon = (int)($_POST['horizon'] ?? 30);

$python = 'C:\\Users\\Elmar Cayaba\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
$script = __DIR__ . '/../scripts/forecast_arima.py';
$cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) .
    ' --series-key ' . escapeshellarg($seriesKey) .
    ' --horizon ' . escapeshellarg((string)$horizon) .
    ' 2>&1';

$output = shell_exec($cmd);
if (!$output) {
    http_response_code(500);
    echo json_encode(['error' => 'No output from ARIMA script.']);
    exit;
}

$data = json_decode($output, true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'ARIMA script error', 'details' => $output]);
    exit;
}

header('Content-Type: application/json');
echo json_encode($data);
