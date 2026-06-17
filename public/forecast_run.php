<?php
ob_start();
require __DIR__ . '/partials/bootstrap.php';
require_once __DIR__ . '/../app/Core/ForecastLogger.php';

function load_daily_series(PDO $pdo, string $seriesKey, int $days = 84): array
{
    if ($seriesKey === 'visits_total') {
        $sql = "SELECT DATE(visit_datetime) AS day_key, COUNT(*) AS value
                FROM visits
                WHERE visit_datetime >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(visit_datetime)
                ORDER BY day_key ASC";
    } elseif ($seriesKey === 'medicine_total') {
        $sql = "SELECT DATE(transaction_datetime) AS day_key, COALESCE(SUM(ABS(quantity)), 0) AS value
                FROM medicine_transactions
                WHERE transaction_type = 'dispensed'
                  AND transaction_datetime >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(transaction_datetime)
                ORDER BY day_key ASC";
    } else {
        $sql = "SELECT series_date AS day_key, value
                FROM timeseries_daily
                WHERE series_key = ?
                  AND series_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY day_key ASC";
    }

    $stmt = $pdo->prepare($sql);
    if ($seriesKey === 'visits_total' || $seriesKey === 'medicine_total') {
        $stmt->execute([$days]);
    } else {
        $stmt->execute([$seriesKey, $days]);
    }

    $rows = $stmt->fetchAll();
    if (empty($rows)) {
        return [];
    }

    $valuesByDate = [];
    foreach ($rows as $row) {
        $valuesByDate[$row['day_key']] = (float)$row['value'];
    }

    $start = new DateTime(array_key_first($valuesByDate));
    $end = new DateTime(array_key_last($valuesByDate));
    $filled = [];

    while ($start <= $end) {
        $day = $start->format('Y-m-d');
        $filled[] = [
            'date' => $day,
            'value' => $valuesByDate[$day] ?? 0.0,
        ];
        $start->modify('+1 day');
    }

    return $filled;
}

function build_fast_forecast(array $history, string $seriesKey, int $horizon): array
{
    if (count($history) < 14) {
        throw new RuntimeException('Not enough history yet. At least 14 days of data are required.');
    }

    $metricLabel = $seriesKey === 'visits_total' ? 'visits' : 'medicine units';
    $recentHistory = array_slice($history, -56);
    $weekdayBuckets = array_fill(0, 7, []);

    foreach ($recentHistory as $row) {
        $weekday = (int)(new DateTime($row['date']))->format('w');
        $weekdayBuckets[$weekday][] = (float)$row['value'];
    }

    $overallAverage = array_sum(array_column($recentHistory, 'value')) / max(count($recentHistory), 1);
    $lastDate = new DateTime(end($history)['date']);
    $forecast = [];

    for ($i = 1; $i <= $horizon; $i++) {
        $forecastDate = (clone $lastDate)->modify('+' . $i . ' day');
        $weekday = (int)$forecastDate->format('w');
        $bucket = $weekdayBuckets[$weekday];
        $value = !empty($bucket) ? array_sum($bucket) / count($bucket) : $overallAverage;

        $spread = !empty($bucket) ? max($bucket) - min($bucket) : max(1.0, $overallAverage * 0.25);
        $forecast[] = [
            'date' => $forecastDate->format('Y-m-d'),
            'value' => round(max(0, $value), 2),
            'lower' => round(max(0, $value - ($spread * 0.35)), 2),
            'upper' => round(max(0, $value + ($spread * 0.35)), 2),
        ];
    }

    $recent30 = array_slice($history, -30);
    $previous30 = array_slice($history, -60, 30);
    $recentAverage = array_sum(array_column($recent30, 'value')) / max(count($recent30), 1);
    $previousAverage = !empty($previous30)
        ? array_sum(array_column($previous30, 'value')) / count($previous30)
        : $recentAverage;
    $forecastAverage = array_sum(array_column($forecast, 'value')) / max(count($forecast), 1);

    if ($previousAverage <= 0) {
        $trendLabel = $forecastAverage > 0 ? 'higher than recent activity' : 'close to recent activity';
    } else {
        $ratio = ($forecastAverage - $previousAverage) / $previousAverage;
        if ($ratio > 0.05) {
            $trendLabel = 'higher than recent activity';
        } elseif ($ratio < -0.05) {
            $trendLabel = 'lower than recent activity';
        } else {
            $trendLabel = 'close to recent activity';
        }
    }

    $peakValue = max(array_column($forecast, 'value'));
    $peakIndex = array_search($peakValue, array_column($forecast, 'value'), true);
    $peakDate = $forecast[$peakIndex]['date'];

    return [
        'series_key' => $seriesKey,
        'generated_on' => date('Y-m-d'),
        'horizon' => $horizon,
        'history' => array_slice($history, -30),
        'forecast' => $forecast,
        'summary' => [
            'intro' => 'Expected average for the next ' . $horizon . ' days is about ' . number_format($forecastAverage, 1) . ' ' . $metricLabel . ' per day, which is ' . $trendLabel . '.',
            'metric_label' => $metricLabel,
            'recent_average' => $recentAverage,
            'previous_average' => $previousAverage,
            'forecast_average' => $forecastAverage,
            'expected_total' => array_sum(array_column($forecast, 'value')),
            'peak_value' => $peakValue,
            'peak_date' => $peakDate,
            'trend_label' => $trendLabel,
            'history_points' => count($history),
            'training_points' => count($recentHistory),
            'history_start' => $history[0]['date'],
            'history_end' => end($history)['date'],
            'model' => 'fast_weekday_average',
            'seasonal_model' => '7-day pattern',
        ],
    ];
}

$seriesKey = $_POST['series_key'] ?? 'visits_total';
$horizon = max(1, (int)($_POST['horizon'] ?? 30));
$fast = ($_POST['fast'] ?? '') === '1';

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json');

try {
    if ($fast) {
        $startTime = microtime(true);
        $runId = ForecastLogger::startRun($seriesKey, $horizon, 'Fast Forecast');
        
        try {
            $history = load_daily_series($pdo, $seriesKey, 180);
            $result = build_fast_forecast($history, $seriesKey, $horizon);
            $executionTime = round(microtime(true) - $startTime, 3);
            
            ForecastLogger::logSuccess(
                $runId,
                (int)($result['summary']['history_points'] ?? 0),
                (int)($result['summary']['training_points'] ?? 0),
                $executionTime,
                $result['forecast']
            );
            
            echo json_encode($result);
        } catch (Throwable $e) {
            $executionTime = round(microtime(true) - $startTime, 3);
            ForecastLogger::logFailure($runId, $e->getMessage(), $executionTime);
            throw $e;
        }
        exit;
    }

    $startTime = microtime(true);
    $runId = ForecastLogger::startRun($seriesKey, $horizon, 'ARIMA');

    $python = getenv('PYTHON_PATH') ?: $_ENV['PYTHON_PATH'] ?: 'python';
    $script = __DIR__ . '/../scripts/forecast_arima.py';
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) .
        ' --series-key ' . escapeshellarg($seriesKey) .
        ' --horizon ' . escapeshellarg((string)$horizon) .
        ' 2>&1';

    $output = shell_exec($cmd);
    $executionTime = round(microtime(true) - $startTime, 3);

    if (!$output) {
        ForecastLogger::logFailure($runId, 'No output from ARIMA script.', $executionTime);
        http_response_code(500);
        echo json_encode(['error' => 'No output from ARIMA script.']);
        exit;
    }

    $data = json_decode($output, true);
    if (!is_array($data)) {
        ForecastLogger::logFailure($runId, 'Forecasting failed (Invalid JSON from Python).', $executionTime);
        http_response_code(500);
        echo json_encode(['error' => 'Forecasting failed.']);
        exit;
    }

    if (!empty($data['error'])) {
        ForecastLogger::logFailure($runId, $data['error'], $executionTime);
        http_response_code(500);
        echo json_encode(['error' => $data['error']]);
        exit;
    }

    // Extract diagnostic information from Python script output
    $diagnostics = null;
    if (isset($data['diagnostics'])) {
        $diagnostics = $data['diagnostics'];
        $diagnostics['model_order'] = $data['summary']['model'] ?? null;
        $diagnostics['seasonal_order'] = $data['summary']['seasonal_model'] ?? null;
    }
    
    ForecastLogger::logSuccess(
        $runId,
        (int)($data['summary']['history_points'] ?? 0),
        (int)($data['summary']['training_points'] ?? 0),
        $executionTime,
        $data['forecast'],
        $diagnostics
    );

    echo json_encode($data);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

