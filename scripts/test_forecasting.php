<?php
/**
 * Test Forecasting Pipeline
 * Tests both visits and medicine forecasting
 */

echo "Testing HealthLogs Forecasting Pipeline\n";
echo "=====================================\n\n";

// Test 1: Direct Python script
echo "1. Testing Python script directly...\n";
$python = 'python';
$script = __DIR__ . '/forecast_arima.py';

// Test visits forecast
$cmd = "$python $script --series-key visits_total --horizon 5 2>&1";
$output = shell_exec($cmd);
$data = json_decode($output, true);

if ($data && isset($data['forecast'])) {
    echo "✓ Visits forecast: " . count($data['forecast']) . " predictions\n";
    echo "  Sample: {$data['forecast'][0]['date']} = {$data['forecast'][0]['value']} visits\n";
} else {
    echo "✗ Visits forecast failed: $output\n";
}

// Test medicine forecast
$cmd = "$python $script --series-key medicine_total --horizon 3 2>&1";
$output = shell_exec($cmd);
$data = json_decode($output, true);

if ($data && isset($data['forecast'])) {
    echo "✓ Medicine forecast: " . count($data['forecast']) . " predictions\n";
    echo "  Sample: {$data['forecast'][0]['date']} = {$data['forecast'][0]['value']} units\n";
} else {
    echo "✗ Medicine forecast failed: $output\n";
}

echo "\n2. Testing time series data availability...\n";
require_once __DIR__ . '/../config/db.php';

$series = $pdo->query("SELECT series_key, COUNT(*) as count, MIN(series_date) as start_date, MAX(series_date) as end_date FROM timeseries_daily GROUP BY series_key")->fetchAll();

foreach ($series as $s) {
    echo "✓ {$s['series_key']}: {$s['count']} data points ({$s['start_date']} to {$s['end_date']})\n";
}

echo "\n3. Testing recent data points...\n";
$recent = $pdo->query("SELECT series_key, series_date, value FROM timeseries_daily WHERE series_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY series_date DESC LIMIT 10")->fetchAll();

foreach ($recent as $r) {
    echo "  {$r['series_key']}: {$r['series_date']} = {$r['value']}\n";
}

echo "\nForecasting pipeline test completed!\n";