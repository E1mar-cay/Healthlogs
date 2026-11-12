<?php
require __DIR__ . '/partials/bootstrap.php';
require_once __DIR__ . '/../app/Core/ForecastLogger.php';
require_once __DIR__ . '/../app/Core/PythonRunner.php';
require_once __DIR__ . '/../app/Core/ArimaService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/forecast.php');
    exit;
}

ob_start();

function load_daily_series(PDO $pdo, string $seriesKey, int $days = 180): array
{
    return ArimaService::loadSeries($pdo, $seriesKey, $days);
}

/**
 * Exponentially-Weighted Seasonal + Damped Trend Snapshot (Holt-Winters Fast Approximation).
 * Uses recency-weighted day-of-week seasonality, damped trend progression across the horizon,
 * horizon-widening statistical confidence bounds, and damped MAPE evaluation.
 */
function build_fast_forecast(array $history, string $seriesKey, int $horizon): array
{
    if (count($history) < 14) {
        throw new RuntimeException('Not enough history yet. At least 14 days of data are required.');
    }

    $metricLabel = $seriesKey === 'visits_total' ? 'visits' : 'medicine units';
    $recentHistory = array_slice($history, -min(84, count($history)));
    $n = count($recentHistory);

    $allValues = array_column($recentHistory, 'value');
    $overallAverage = array_sum($allValues) / max($n, 1);

    // 1. Exponentially-weighted weekday buckets (more recent weeks carry higher weight: 0.85^weeksAgo)
    $weekdayWeightedSum = array_fill(0, 7, 0.0);
    $weekdayWeightTotal = array_fill(0, 7, 0.0);
    $weekdayRawValues = array_fill(0, 7, []);

    foreach ($recentHistory as $idx => $row) {
        $weekday = (int)(new DateTime($row['date']))->format('w');
        $daysAgo = ($n - 1) - $idx;
        $weeksAgo = $daysAgo / 7.0;
        $weight = pow(0.85, $weeksAgo);
        $val = (float)$row['value'];

        $weekdayWeightedSum[$weekday] += $val * $weight;
        $weekdayWeightTotal[$weekday] += $weight;
        $weekdayRawValues[$weekday][] = $val;
    }

    $weekdayBaseline = [];
    for ($w = 0; $w < 7; $w++) {
        $weekdayBaseline[$w] = $weekdayWeightTotal[$w] > 0
            ? ($weekdayWeightedSum[$w] / $weekdayWeightTotal[$w])
            : $overallAverage;
    }

    // Normalize seasonal indices around recent 28-day level
    $recent28 = array_slice($allValues, -min(28, $n));
    $prev28 = array_slice($allValues, -min(56, $n), max(1, min(28, $n - count($recent28))));
    $recent28Avg = array_sum($recent28) / max(count($recent28), 1);
    $prev28Avg = !empty($prev28) ? (array_sum($prev28) / count($prev28)) : $recent28Avg;

    $meanWeekdayBaseline = array_sum($weekdayBaseline) / 7.0;
    $seasonalFactors = [];
    for ($w = 0; $w < 7; $w++) {
        $seasonalFactors[$w] = $meanWeekdayBaseline > 0
            ? ($weekdayBaseline[$w] / $meanWeekdayBaseline)
            : 1.0;
    }

    // Damped daily trend slope derived from 28-day momentum (capped to prevent runaway extrapolation)
    $rawDailySlope = ($recent28Avg - $prev28Avg) / 28.0;
    $maxDailySlope = $recent28Avg * 0.008; // max ±0.8% per day
    $dailySlope = max(-$maxDailySlope, min($maxDailySlope, $rawDailySlope));
    $dampingPhi = 0.95;

    // 2. Compute in-sample fitted values and error metrics (MAE, RMSE, damped MAPE)
    $absErrors = [];
    $squaredErrors = [];
    $pctErrors = [];
    $mapeFloor = max(1.0, $overallAverage * 0.25);

    foreach ($recentHistory as $idx => $row) {
        $w = (int)(new DateTime($row['date']))->format('w');
        // Local 21-day level around $idx for realistic in-sample seasonal fit
        $winStart = max(0, $idx - 14);
        $winSlice = array_slice($allValues, $winStart, min(21, $n - $winStart));
        $localLevel = !empty($winSlice) ? (array_sum($winSlice) / count($winSlice)) : $overallAverage;

        $fitted = max(0.0, $localLevel * $seasonalFactors[$w]);
        $actual = (float)$row['value'];
        $err = $actual - $fitted;
        $absErrors[] = abs($err);
        $squaredErrors[] = $err * $err;
        if ($actual > 0) {
            $pctErrors[] = (abs($err) / max($actual, $mapeFloor)) * 100.0;
        }
    }

    $mae = $n > 0 ? array_sum($absErrors) / $n : 0.0;
    $rmse = $n > 0 ? sqrt(array_sum($squaredErrors) / $n) : 1.0;
    $mape = !empty($pctErrors) ? array_sum($pctErrors) / count($pctErrors) : 0.0;
    $accuracyRating = $mape < 10.0 ? 'High Accuracy' : ($mape < 20.0 ? 'Good Fit' : ($mape < 50.0 ? 'Reasonable' : 'High Variance'));

    // 3. Project forward $horizon days from today (or latest history date if in future)
    $todayDt = new DateTime(date('Y-m-d'));
    $lastHistoryDt = new DateTime(end($history)['date']);
    $lastDate = $lastHistoryDt > $todayDt ? $lastHistoryDt : $todayDt;

    $forecast = [];
    $cumulativeDampedTrend = 0.0;

    for ($h = 1; $h <= $horizon; $h++) {
        $forecastDate = (clone $lastDate)->modify('+' . $h . ' day');
        $weekday = (int)$forecastDate->format('w');

        $cumulativeDampedTrend += $dailySlope * pow($dampingPhi, $h);
        $projectedLevel = max(0.0, $recent28Avg + $cumulativeDampedTrend);
        $pointValue = max(0.0, $projectedLevel * $seasonalFactors[$weekday]);

        // Horizon-widening 80% prediction interval based on weekday std dev & RMSE
        $wVals = $weekdayRawValues[$weekday];
        $wMean = !empty($wVals) ? (array_sum($wVals) / count($wVals)) : $overallAverage;
        $wVar = 0.0;
        foreach ($wVals as $wv) {
            $wVar += ($wv - $wMean) ** 2;
        }
        $wStd = count($wVals) > 1 ? sqrt($wVar / (count($wVals) - 1)) : $rmse;
        $blendedSe = (0.6 * $wStd) + (0.4 * $rmse);
        $horizonMargin = 1.28 * $blendedSe * sqrt(1.0 + (($h - 1) * 0.03));

        $forecast[] = [
            'date' => $forecastDate->format('Y-m-d'),
            'value' => round($pointValue, 2),
            'lower' => round(max(0.0, $pointValue - $horizonMargin), 2),
            'upper' => round(max(0.0, $pointValue + $horizonMargin), 2),
        ];
    }

    $recent30 = array_slice($history, -min(30, count($history)));
    $previous30 = array_slice($history, -min(60, count($history)), max(1, min(30, count($history) - count($recent30))));
    $recentAverage = array_sum(array_column($recent30, 'value')) / max(count($recent30), 1);
    $previousAverage = !empty($previous30)
        ? array_sum(array_column($previous30, 'value')) / count($previous30)
        : $recentAverage;
    $forecastAverage = array_sum(array_column($forecast, 'value')) / max(count($forecast), 1);

    if ($recentAverage <= 0) {
        $trendLabel = $forecastAverage > 0 ? 'higher than recent activity' : 'close to recent activity';
    } else {
        $ratio = ($forecastAverage - $recentAverage) / $recentAverage;
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

    $metrics = [
        'mae' => round($mae, 4),
        'rmse' => round($rmse, 4),
        'mape' => round($mape, 2),
        'accuracy_rating' => $accuracyRating,
        'sample_size' => $n,
    ];

    return [
        'series_key' => $seriesKey,
        'generated_on' => date('Y-m-d'),
        'horizon' => $horizon,
        'history' => array_slice($history, -30),
        'forecast' => $forecast,
        'metrics' => $metrics,
        'summary' => [
            'intro' => 'Expected average for the next ' . $horizon . ' days is about ' . number_format($forecastAverage, 1) . ' ' . $metricLabel . ' per day, which is ' . $trendLabel . '.',
            'metric_label' => $metricLabel,
            'recent_average' => round($recentAverage, 2),
            'previous_average' => round($previousAverage, 2),
            'forecast_average' => round($forecastAverage, 2),
            'expected_total' => round(array_sum(array_column($forecast, 'value')), 2),
            'peak_value' => round($peakValue, 2),
            'peak_date' => $peakDate,
            'trend_label' => $trendLabel,
            'history_points' => count($history),
            'training_points' => $n,
            'history_start' => $history[0]['date'],
            'history_end' => end($history)['date'],
            'model' => 'Holt-Winters Seasonal',
            'seasonal_model' => '7-day weighted pattern',
            'mae' => $metrics['mae'],
            'rmse' => $metrics['rmse'],
            'mape' => $metrics['mape'],
            'accuracy_rating' => $metrics['accuracy_rating'],
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
        $history = load_daily_series($pdo, $seriesKey, 180);
        $result = build_fast_forecast($history, $seriesKey, $horizon);
        echo json_encode($result);
        exit;
    }

    $startTime = microtime(true);
    $runId = ForecastLogger::startRun($seriesKey, $horizon, 'ARIMA');

    $data = null;

    // 1. Try Python execution if available in this environment
    if (PythonRunner::isAvailable(__DIR__ . '/..')) {
        $python = PythonRunner::executable(__DIR__ . '/..');
        $script = __DIR__ . '/../scripts/forecast_arima.py';
        $cmd = PythonRunner::buildCommand($python, $script, [
            '--series-key' => $seriesKey,
            '--horizon' => (string)$horizon,
        ]);

        $output = @shell_exec($cmd);
        if ($output) {
            $parsed = json_decode($output, true);
            if (is_array($parsed) && empty($parsed['error']) && isset($parsed['forecast'], $parsed['summary'])) {
                $data = $parsed;
            }
        }
    }

    // 2. Native Pure-PHP ARIMA & Statistical Engine (cloud serverless / no-python environment)
    if (!$data) {
        $data = ArimaService::forecast($pdo, $seriesKey, $horizon);
    }

    $executionTime = round(microtime(true) - $startTime, 3);

    // Extract diagnostic information
    $metrics = $data['metrics'] ?? null;
    $diagnostics = null;
    if (isset($data['diagnostics'])) {
        $diagnostics = $data['diagnostics'];
        $diagnostics['model_order'] = $data['summary']['model'] ?? null;
        $diagnostics['seasonal_order'] = $data['summary']['seasonal_model'] ?? null;
        if ($metrics) {
            $diagnostics['mae'] = $metrics['mae'] ?? null;
            $diagnostics['rmse'] = $metrics['rmse'] ?? null;
            $diagnostics['mape'] = $metrics['mape'] ?? null;
        }
    }

    ForecastLogger::logSuccess(
        $runId,
        (int)($data['summary']['history_points'] ?? 0),
        (int)($data['summary']['training_points'] ?? 0),
        $executionTime,
        $data['forecast'],
        $diagnostics,
        $metrics
    );

    echo json_encode($data);
} catch (Throwable $e) {
    if (isset($runId)) {
        ForecastLogger::logFailure($runId, $e->getMessage(), round(microtime(true) - ($startTime ?? microtime(true)), 3));
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
