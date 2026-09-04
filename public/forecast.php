<?php
$pageTitle = 'Forecasting';
require __DIR__ . '/partials/bootstrap.php';
require_once __DIR__ . '/../app/Core/ForecastLogger.php';
require __DIR__ . '/partials/header.php';

$seriesOptions = [
    'visits_total' => 'Patient Visits',
    'medicine_total' => 'Medicine Demand',
];

$seriesUnits = [
    'visits_total' => 'visits',
    'medicine_total' => 'units',
];

$seriesKey = $_POST['series_key'] ?? 'visits_total';
$horizon = max(1, (int)($_POST['horizon'] ?? 30));

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $error = 'No output from forecasting script.';
        ForecastLogger::logFailure($runId, $error, $executionTime);
    } else {
        $data = json_decode($output, true);
        if (!is_array($data)) {
            $error = 'Forecasting failed. Please try again.';
            ForecastLogger::logFailure($runId, 'Invalid output from python: ' . substr($output, 0, 500), $executionTime);
        } elseif (!empty($data['error'])) {
            $error = $data['error'];
            ForecastLogger::logFailure($runId, $error, $executionTime);
        } elseif (!isset($data['forecast'], $data['summary'])) {
            $error = 'Forecast response is incomplete.';
            ForecastLogger::logFailure($runId, $error, $executionTime);
        } else {
            $result = $data;

            // Extract diagnostics and metrics
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
        }
    }
}

$summary = $result['summary'] ?? null;
$forecastRows = $result['forecast'] ?? [];
$historyRows = $result['history'] ?? [];
$metrics = $result['metrics'] ?? ($summary ? [
    'mae' => $summary['mae'] ?? null,
    'rmse' => $summary['rmse'] ?? null,
    'mape' => $summary['mape'] ?? null,
    'accuracy_rating' => $summary['accuracy_rating'] ?? null,
] : null);
$unitLabel = $seriesUnits[$seriesKey] ?? 'items';
$forecastGenerated = $_SERVER['REQUEST_METHOD'] === 'POST' && $result !== null;

// Report 1: Admission / Consultation forecasting inputs (last 16 weeks)
$consultationWeekly = [];
$admissionWeekly = [];
$consultationForecast = [];
$admissionForecast = [];

try {
    $weeklySql = "SELECT
            YEARWEEK(visit_datetime, 1) AS week_key,
            MIN(DATE(visit_datetime)) AS week_start,
            COUNT(*) AS total_visits,
            SUM(CASE WHEN visit_type = 'general' THEN 1 ELSE 0 END) AS consultation_visits
        FROM visits
        WHERE visit_datetime >= DATE_SUB(CURDATE(), INTERVAL 140 DAY)
        GROUP BY YEARWEEK(visit_datetime, 1)
        ORDER BY week_key ASC";
    $weeklyRows = $pdo->query($weeklySql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeklyRows as $row) {
        $consultationWeekly[] = [
            'week_start' => $row['week_start'],
            'value' => (float)$row['consultation_visits'],
        ];
        $admissionWeekly[] = [
            'week_start' => $row['week_start'],
            'value' => (float)$row['total_visits'],
        ];
    }

    $buildSimpleForecast = function (array $points, int $horizon = 4): array {
        if (empty($points)) {
            return [];
        }
        $values = array_map(fn($x) => (float)$x['value'], $points);
        $recentSlice = array_slice($values, -8);
        $olderSlice = array_slice($values, -16, 8);

        $recentAvg = count($recentSlice) ? array_sum($recentSlice) / count($recentSlice) : 0.0;
        $olderAvg = count($olderSlice) ? array_sum($olderSlice) / count($olderSlice) : $recentAvg;
        $step = ($recentAvg - $olderAvg) / max(1, $horizon);

        $forecast = [];
        for ($i = 1; $i <= $horizon; $i++) {
            $forecast[] = max(0.0, $recentAvg + ($step * $i));
        }
        return $forecast;
    };

    $consultationForecast = $buildSimpleForecast($consultationWeekly, 4);
    $admissionForecast = $buildSimpleForecast($admissionWeekly, 4);
} catch (Throwable $e) {
    $consultationWeekly = [];
    $admissionWeekly = [];
    $consultationForecast = [];
    $admissionForecast = [];
}

// Report 2: Seasonal disease (month-of-year pattern)
$seasonalDiseaseRows = [];
$seasonalTopMonths = [];

try {
    $seasonalSql = "SELECT
            MONTH(diagnosed_on) AS month_no,
            DATE_FORMAT(diagnosed_on, '%b') AS month_label,
            COUNT(*) AS total_cases
        FROM patient_conditions
        WHERE diagnosed_on IS NOT NULL
        GROUP BY MONTH(diagnosed_on), DATE_FORMAT(diagnosed_on, '%b')
        ORDER BY MONTH(diagnosed_on)";
    $seasonalDiseaseRows = $pdo->query($seasonalSql)->fetchAll(PDO::FETCH_ASSOC);

    $sortedSeason = $seasonalDiseaseRows;
    usort($sortedSeason, fn($a, $b) => (int)$b['total_cases'] <=> (int)$a['total_cases']);
    $seasonalTopMonths = array_slice($sortedSeason, 0, 3);
} catch (Throwable $e) {
    $seasonalDiseaseRows = [];
    $seasonalTopMonths = [];
}

// Report 3: Disease case trends (top 5 conditions, last 12 months)
$diseaseTrendMonths = [];
$diseaseTrendSeries = [];
$topDiseaseNames = [];

try {
    $topDiseaseSql = "SELECT condition_name, COUNT(*) AS total_cases
        FROM patient_conditions
        WHERE diagnosed_on IS NOT NULL
        GROUP BY condition_name
        ORDER BY total_cases DESC
        LIMIT 5";
    $topDiseases = $pdo->query($topDiseaseSql)->fetchAll(PDO::FETCH_ASSOC);
    $topDiseaseNames = array_map(fn($d) => $d['condition_name'], $topDiseases);

    if (!empty($topDiseaseNames)) {
        $trendSql = "SELECT
                DATE_FORMAT(diagnosed_on, '%Y-%m') AS ym,
                condition_name,
                COUNT(*) AS total_cases
            FROM patient_conditions
            WHERE diagnosed_on >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND diagnosed_on IS NOT NULL
              AND condition_name IN (" . implode(',', array_fill(0, count($topDiseaseNames), '?')) . ")
            GROUP BY DATE_FORMAT(diagnosed_on, '%Y-%m'), condition_name
            ORDER BY ym ASC";
        $trendStmt = $pdo->prepare($trendSql);
        $trendStmt->execute($topDiseaseNames);
        $trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        $monthMap = [];
        foreach ($trendRows as $row) {
            $monthMap[$row['ym']] = true;
        }
        $diseaseTrendMonths = array_keys($monthMap);
        sort($diseaseTrendMonths);

        foreach ($topDiseaseNames as $name) {
            $diseaseTrendSeries[$name] = array_fill(0, count($diseaseTrendMonths), 0);
        }

        $monthIndex = array_flip($diseaseTrendMonths);
        foreach ($trendRows as $row) {
            $name = $row['condition_name'];
            $ym = $row['ym'];
            if (isset($diseaseTrendSeries[$name], $monthIndex[$ym])) {
                $diseaseTrendSeries[$name][$monthIndex[$ym]] = (int)$row['total_cases'];
            }
        }
    }
} catch (Throwable $e) {
    $diseaseTrendMonths = [];
    $diseaseTrendSeries = [];
    $topDiseaseNames = [];
}

if ($summary) {
    $firstWeek = array_slice($forecastRows, 0, min(7, count($forecastRows)));
    $laterPeriod = count($forecastRows) > 7 ? array_slice($forecastRows, 7) : [];
    $recentForecastRows = array_slice($forecastRows, 0, min(6, count($forecastRows)));

    $firstWeekAverage = count($firstWeek) ? array_sum(array_column($firstWeek, 'value')) / count($firstWeek) : 0;
    $laterAverage = count($laterPeriod) ? array_sum(array_column($laterPeriod, 'value')) / count($laterPeriod) : $summary['forecast_average'];
    $changeVsRecent = $summary['forecast_average'] - $summary['recent_average'];
    $changeWord = abs($changeVsRecent) < 0.5 ? 'about the same as' : ($changeVsRecent > 0 ? 'higher than' : 'lower than');

    $planningNotes = [
        'Plan for around ' . number_format($summary['forecast_average'], 1) . ' ' . $unitLabel . ' per day.',
        'That is ' . $changeWord . ' the recent average of ' . number_format($summary['recent_average'], 1) . '.',
        'The highest single-day estimate is ' . number_format($summary['peak_value'], 1) . ' on ' . $summary['peak_date'] . '.',
    ];
}
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Forecasting</div>
      <div class="text-2xl font-semibold">Planning Forecast</div>
      <p class="text-sm text-slate-500 mt-1">Shows the recent trend and the expected daily demand for the selected period.</p>
    </div>
    <div class="text-sm text-slate-500">
      Use this as a planning guide, not an exact daily promise.
    </div>
  </div>

  <form class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4" method="post">
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">What to forecast</label>
      <select name="series_key" class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($seriesOptions as $key => $label): ?>
          <option value="<?= h($key) ?>" <?= $seriesKey === $key ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Days to look ahead</label>
      <input name="horizon" value="<?= h($horizon) ?>" type="number" min="1" max="90" class="mt-1 w-full border rounded px-3 py-2" />
    </div>
    <div class="md:col-span-3">
      <button class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow" type="submit">Generate Forecast</button>
    </div>
  </form>

</div>

<div class="mt-6 bg-white p-5 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Forecast Snapshot</div>
      <div class="text-lg font-semibold">Quick View</div>
      <p class="text-sm text-slate-500 mt-1" id="snapshotIntro">Loading quick forecast snapshot...</p>
    </div>
    <div class="text-sm text-slate-500" id="snapshotMeta">Using the selected forecast settings.</div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-400">Average / Day</div>
      <div class="text-2xl font-semibold mt-2" id="snapshotAverage">--</div>
      <div class="text-sm text-slate-500 mt-1" id="snapshotUnit">--</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-400">Peak Day</div>
      <div class="text-2xl font-semibold mt-2" id="snapshotPeak">--</div>
      <div class="text-sm text-slate-500 mt-1" id="snapshotPeakDate">--</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-400">Expected Total</div>
      <div class="text-2xl font-semibold mt-2" id="snapshotTotal">--</div>
      <div class="text-sm text-slate-500 mt-1" id="snapshotHorizon">--</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="flex items-center justify-between">
        <span class="text-xs uppercase tracking-widest text-slate-400">Model Error (MAE/MAPE)</span>
        <span id="snapshotRatingBadge" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">Fit</span>
      </div>
      <div class="text-2xl font-semibold mt-2 font-mono" id="snapshotMape">--</div>
      <div class="text-sm text-slate-500 mt-1" id="snapshotMae">--</div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2">
      <div class="text-sm text-slate-500 mb-3">Recent Forecast</div>
      <div id="snapshotDays" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-sm text-slate-500">Report Summary</div>
      <ul class="mt-4 space-y-3 text-sm text-slate-700" id="snapshotSummary">
        <li>Loading summary...</li>
      </ul>
    </div>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="text-sm text-slate-500">Forecast Reports</div>
  <div class="text-lg font-semibold">Admission / Consultation Forecasting</div>
  <p class="text-sm text-slate-500 mt-1">Based on weekly visit activity. Consultation uses general visits; admission uses total visits as intake proxy.</p>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
    <div class="rounded-lg border border-slate-200 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-500">Consultation Forecast (Next 4 Weeks)</div>
      <div class="mt-3 space-y-2 text-sm text-slate-700">
        <?php if (!empty($consultationForecast)): ?>
          <?php foreach ($consultationForecast as $i => $value): ?>
            <div>Week <?= h((string)($i + 1)) ?>: <strong><?= h(number_format($value, 1)) ?></strong> consultations</div>
          <?php endforeach; ?>
        <?php else: ?>
          <div>Not enough consultation data to forecast yet.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="rounded-lg border border-slate-200 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-500">Admission Forecast (Next 4 Weeks)</div>
      <div class="mt-3 space-y-2 text-sm text-slate-700">
        <?php if (!empty($admissionForecast)): ?>
          <?php foreach ($admissionForecast as $i => $value): ?>
            <div>Week <?= h((string)($i + 1)) ?>: <strong><?= h(number_format($value, 1)) ?></strong> admissions</div>
          <?php endforeach; ?>
        <?php else: ?>
          <div>Not enough admission data to forecast yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 bg-white p-6 rounded shadow">
    <div class="text-sm text-slate-500">Seasonal Disease</div>
    <div class="text-lg font-semibold">Month-Of-Year Case Pattern</div>
    <canvas id="seasonalDiseaseChart" height="120" class="mt-4"></canvas>
  </div>
  <div class="bg-white p-6 rounded shadow">
    <div class="text-sm text-slate-500">Peak Months</div>
    <div class="text-lg font-semibold">Highest Disease Seasons</div>
    <ul class="mt-4 space-y-3 text-sm text-slate-700">
      <?php if (!empty($seasonalTopMonths)): ?>
        <?php foreach ($seasonalTopMonths as $row): ?>
          <li class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0">
            <?= h($row['month_label']) ?>: <?= h((string)$row['total_cases']) ?> cases
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li>No seasonal disease data yet.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="text-sm text-slate-500">Disease Case Trends</div>
  <div class="text-lg font-semibold">Top Disease Trends (Last 12 Months)</div>
  <canvas id="diseaseTrendChart" height="120" class="mt-4"></canvas>
</div>

<!-- Model Execution Logs History Section -->
<?php
// Query recent runs
$recentRuns = ForecastLogger::getRecentLogs(30);
$successRunsCount = count(array_filter($recentRuns, fn($r) => $r['status'] === 'success'));
$failedRunsCount = count(array_filter($recentRuns, fn($r) => $r['status'] === 'failed'));
?>
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Analytics History</div>
      <div class="text-lg font-semibold">Model Execution Logs &amp; ARIMA Parameters</div>
      <p class="text-sm text-slate-500 mt-1">Audit log of fitted models, training points, and statistical parameters.</p>
    </div>
    
    <!-- Filter Toggle: Successful (default) | All Runs | Failed -->
    <div class="flex flex-wrap sm:inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-xs font-medium self-start md:self-auto gap-1 sm:gap-0">
      <button type="button" class="log-filter-btn px-3 py-1.5 rounded-md transition-all bg-white text-slate-900 shadow-xs font-semibold" data-filter="success">
        <i class="fas fa-check-circle text-emerald-600 mr-1"></i> Successful
        <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold"><?= $successRunsCount ?></span>
      </button>
      <button type="button" class="log-filter-btn px-3 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900" data-filter="all">
        All Runs
        <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold"><?= count($recentRuns) ?></span>
      </button>
      <?php if ($failedRunsCount > 0): ?>
        <button type="button" class="log-filter-btn px-3 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900" data-filter="failed">
          <i class="fas fa-exclamation-triangle text-amber-600 mr-1"></i> Failed
          <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold"><?= $failedRunsCount ?></span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-6 overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
    <table class="w-full text-left border-collapse text-sm min-w-[760px]">
      <thead>
        <tr class="border-b border-slate-200 text-slate-400 font-medium text-xs">
          <th class="py-3 px-4">Run Time</th>
          <th class="py-3 px-4">Series Key</th>
          <th class="py-3 px-4">Horizon</th>
          <th class="py-3 px-4">Model Type</th>
          <th class="py-3 px-4">Fitted Model</th>
          <th class="py-3 px-3">MAE</th>
          <th class="py-3 px-3">RMSE</th>
          <th class="py-3 px-3">MAPE</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-slate-700" id="logTableBody">
        <tr id="noFilteredLogsRow" class="hidden">
          <td colspan="10" class="py-8 px-4 text-center text-slate-400">
            <i class="fas fa-filter mr-1.5 text-slate-300"></i> No logs match the selected filter.
          </td>
        </tr>
        <?php if (empty($recentRuns)): ?>
          <tr>
            <td colspan="10" class="py-4 px-4 text-center text-slate-400">No forecasting logs recorded yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentRuns as $run): 
            $seriesLabel = $seriesOptions[$run['series_key']] ?? $run['series_key'];
            $statusClass = $run['status'] === 'success' 
              ? 'bg-green-50 text-green-700 border border-green-200' 
              : 'bg-red-50 text-red-700 border border-red-200';
            
            $modelOrderText = 'N/A';
            if ($run['model_type'] === 'ARIMA') {
                $order = $run['model_order'] ?? '';
                $seasonal = $run['seasonal_order'] ?? '';
                if ($order) {
                    $modelOrderText = 'ARIMA' . $order;
                    if ($seasonal && $seasonal !== '(0, 0, 0, 0)' && $seasonal !== '(0, 0, 0, 1)' && $seasonal !== '(0, 0, 0, 7)') {
                        $modelOrderText .= ' x ' . $seasonal;
                    }
                }
            } else {
                $modelOrderText = 'Fast Average';
            }
          ?>
            <tr class="log-row hover:bg-slate-50 transition-colors" data-status="<?= h($run['status']) ?>">
              <td class="py-3 px-4 whitespace-nowrap">
                <?= h(date('M d, Y h:i A', strtotime($run['created_at']))) ?>
              </td>
              <td class="py-3 px-4 font-medium">
                <?= h($seriesLabel) ?>
              </td>
              <td class="py-3 px-4">
                <?= h($run['horizon']) ?> days
              </td>
              <td class="py-3 px-4">
                <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                  <?= h($run['model_type']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-xs font-semibold text-slate-700">
                <?= h($modelOrderText === 'N/A' ? 'Auto-fitted model' : $modelOrderText) ?>
              </td>
              <td class="py-3 px-3 font-mono text-xs text-slate-700">
                <?= $run['mae'] !== null ? h(number_format((float)$run['mae'], 2)) : '<span class="text-slate-300">--</span>' ?>
              </td>
              <td class="py-3 px-3 font-mono text-xs text-slate-700">
                <?= $run['rmse'] !== null ? h(number_format((float)$run['rmse'], 2)) : '<span class="text-slate-300">--</span>' ?>
              </td>
              <td class="py-3 px-3 text-xs font-medium">
                <?php if ($run['mape'] !== null): ?>
                  <?php
                    $mVal = (float)$run['mape'];
                    $mClass = $mVal < 20.0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($mVal < 50.0 ? 'text-blue-700 bg-blue-50 border-blue-200' : 'text-amber-700 bg-amber-50 border-amber-200');
                  ?>
                  <span class="px-2 py-0.5 rounded border text-[11px] font-semibold <?= $mClass ?>">
                    <?= h(number_format($mVal, 1)) ?>%
                  </span>
                <?php else: ?>
                  <span class="text-slate-300">--</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium <?= $statusClass ?>">
                  <?= h(ucfirst($run['status'])) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <?php if ($run['status'] === 'success'): ?>
                  <button type="button" 
                          class="view-run-details-btn text-teal-600 hover:text-teal-900 border border-teal-200 hover:bg-teal-50 px-3 py-1 rounded transition-colors text-xs font-medium"
                          data-run-id="<?= (int)$run['id'] ?>">
                    <i class="fas fa-eye mr-1"></i> View Details
                  </button>
                <?php else: ?>
                  <button type="button" 
                          class="view-run-error-btn text-red-600 hover:text-red-900 border border-red-200 hover:bg-red-50 px-3 py-1 rounded transition-colors text-xs font-medium"
                          data-error-msg="<?= h($run['error_message'] ?? 'Unknown script error') ?>">
                    <i class="fas fa-exclamation-triangle mr-1"></i> View Error
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal for Model Log Details -->
<div id="modelLogModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <!-- Backdrop -->
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="modelLogModalBackdrop"></button>
  
  <div class="flex min-h-screen items-center justify-center p-2 sm:p-4">
    <!-- Modal Card -->
    <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-100 bg-slate-50">
        <div>
          <h3 class="text-base sm:text-lg font-semibold text-slate-900" id="modalTitle">Model Details</h3>
          <p class="text-xs text-slate-500 mt-0.5" id="modalSubtitle">Run ID: --</p>
        </div>
        <button type="button" id="modelLogModalClose" class="text-slate-400 hover:text-slate-600 focus:outline-none rounded-lg p-1 hover:bg-slate-100 transition-colors">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-4 sm:space-y-6 flex-1">
        
        <!-- Run Info Summary Card -->
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 p-3 sm:p-4 bg-slate-50 border border-slate-100 rounded-xl">
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">Model Type</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalModelType">--</div>
          </div>
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">Execution Time</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalExecTime">--</div>
          </div>
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">History Size</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalHistorySize">--</div>
          </div>
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">Training Points</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalTrainingPoints">--</div>
          </div>
        </div>

        <!-- Model Evaluation & Accuracy (MAE, RMSE, MAPE) -->
        <div class="border border-slate-200 rounded-xl p-5 bg-gradient-to-r from-slate-50 to-teal-50/20">
          <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-3">
            <h4 class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
              <i class="fas fa-chart-line text-teal-600"></i> Model Accuracy & Error Evaluation
            </h4>
            <span id="modalAccuracyRating" class="px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">Statistical Fit</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-slate-500 font-medium">MAE</span>
                <span class="text-[10px] text-slate-400 font-mono">Mean Absolute</span>
              </div>
              <div class="text-xl font-bold text-slate-800 mt-1 font-mono" id="modalMae">--</div>
              <div class="text-xs text-slate-500 mt-1" id="modalMaeDesc">Average deviation per day</div>
            </div>
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-slate-500 font-medium">RMSE</span>
                <span class="text-[10px] text-slate-400 font-mono">Root Mean Sq.</span>
              </div>
              <div class="text-xl font-bold text-slate-800 mt-1 font-mono" id="modalRmse">--</div>
              <div class="text-xs text-slate-500 mt-1">Penalizes large outlier spikes</div>
            </div>
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-slate-500 font-medium">MAPE</span>
                <span class="text-[10px] text-slate-400 font-mono">Percentage Error</span>
              </div>
              <div class="text-xl font-bold text-slate-800 mt-1 font-mono" id="modalMape">--</div>
              <div class="text-xs text-slate-500 mt-1">Relative percentage error</div>
            </div>
          </div>
        </div>

        <!-- Key Takeaways & Planning Insights -->
        <div id="modalSummarySection" class="border border-slate-150 rounded-xl p-5 bg-slate-50/50">
          <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-150 pb-2 mb-3">
            <i class="fas fa-lightbulb mr-1.5 text-amber-500"></i> Key Takeaways & Planning Insights
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-3">
            <div class="space-y-3">
              <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">Expected Daily Average</span>
                <span class="font-bold text-slate-800" id="modalInsightAvg">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Total Expected Volume</span>
                <span class="font-bold text-slate-800" id="modalInsightTotal">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Planning Period (Horizon)</span>
                <span class="font-semibold text-slate-800" id="modalInsightHorizon">--</span>
              </div>
            </div>
            <div class="space-y-3">
              <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">Busiest Expected Day</span>
                <span class="font-bold text-slate-800" id="modalInsightPeak">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Quietest Expected Day</span>
                <span class="font-bold text-slate-800" id="modalInsightQuiet">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Usual Daily Range</span>
                <span class="font-semibold text-slate-800" id="modalInsightRange">--</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Forecast Predictions Table -->
        <div>
          <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">
            <i class="fas fa-table mr-1.5 text-blue-500"></i> Forecast Results (Daily Predictions)
          </h4>
          <div class="border border-slate-150 rounded-xl overflow-hidden max-h-[40vh] overflow-y-auto">
            <table class="w-full text-left border-collapse text-xs relative">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-150 text-slate-500 sticky top-0 z-10 shadow-sm">
                  <th class="py-2.5 px-4">Forecast Date</th>
                  <th class="py-2.5 px-4 text-right">Expected Amount</th>
                  <th class="py-2.5 px-4 text-right">Min Expected</th>
                  <th class="py-2.5 px-4 text-right">Max Expected</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700" id="modalPredictionsBody">
                <!-- Dynamic rows -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="flex justify-end items-center px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button type="button" id="modelLogModalCloseBtn" class="bg-slate-900 hover:bg-slate-800 text-white text-xs px-4 py-2 rounded-lg font-medium shadow transition-colors">
          Close Details
        </button>
      </div>

    </div>
  </div>
</div>

<?php if ($summary): ?>
  <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-500">What The Forecast Says</div>
        <p class="mt-2 text-lg font-medium text-slate-900"><?= h($summary['intro']) ?></p>
        <div class="mt-3 text-sm text-slate-600">
          Based on <?= h($summary['history_points']) ?> days of history from <?= h($summary['history_start']) ?> to <?= h($summary['history_end']) ?>, with the most recent <?= h($summary['training_points'] ?? $summary['history_points']) ?> days used for the model.
        </div>
      </div>
      <div class="print:hidden">
        <button class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow" type="button" onclick="window.print()">Print Forecast</button>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mt-6">
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Average Per Day</div>
      <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['forecast_average'], 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1"><?= h($unitLabel) ?> expected each day</div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Expected Total</div>
      <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['expected_total'], 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1">Across <?= h($horizon) ?> days</div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Busiest Day</div>
      <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['peak_value'], 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1"><?= h($summary['peak_date']) ?></div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Compared To Recent Days</div>
      <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['recent_average'], 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1">Recent daily average</div>
    </div>
  </div>

  <!-- Model Evaluation & Error Metrics (MAE, RMSE, MAPE) Section -->
  <?php if ($metrics && (isset($metrics['mae']) || isset($metrics['rmse']) || isset($metrics['mape']))): ?>
    <div class="mt-6 bg-white p-6 rounded shadow border-l-4 border-teal-600">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-150">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2.5 py-0.5 rounded border border-teal-200">
              <i class="fas fa-microchip mr-1"></i> Model Evaluation
            </span>
            <span class="text-xs text-slate-400">Quantitative Statistical Analysis</span>
          </div>
          <h3 class="text-xl font-bold text-slate-900 mt-1">Forecast Error Metrics: MAE, RMSE &amp; MAPE</h3>
          <p class="text-sm text-slate-500 mt-0.5">
            Model fit accuracy evaluated across <?= h($summary['training_points'] ?? 180) ?> daily data points in the training window.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <?php
            $mapeVal = (float)($metrics['mape'] ?? 0);
            $ratingText = $metrics['accuracy_rating'] ?? ($mapeVal < 10.0 ? 'High Accuracy' : ($mapeVal < 20.0 ? 'Good Fit' : ($mapeVal < 50.0 ? 'Reasonable' : 'High Variance')));
            $badgeBg = $mapeVal < 20.0 ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : ($mapeVal < 50.0 ? 'bg-blue-50 text-blue-800 border-blue-300' : 'bg-amber-50 text-amber-800 border-amber-300');
          ?>
          <div class="text-left md:text-right">
            <div class="text-xs text-slate-400 uppercase tracking-widest font-medium">Model Rating</div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border mt-0.5 <?= $badgeBg ?>">
              <span class="h-2 w-2 rounded-full bg-current"></span>
              <?= h($ratingText) ?>
            </span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <!-- MAE Card -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 relative overflow-hidden group hover:border-teal-400 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold tracking-wider text-slate-500">Mean Absolute Error</span>
            <span class="text-xs font-mono px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-2xs font-semibold">MAE</span>
          </div>
          <div class="text-3xl font-extrabold text-slate-900 mt-3 font-mono">
            <?= h(number_format((float)($metrics['mae'] ?? 0), 2)) ?>
            <span class="text-sm font-normal text-slate-500 font-sans"><?= h($unitLabel) ?></span>
          </div>
          <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            On average, daily predictions deviate from actual activity by <strong>&plusmn;<?= h(number_format((float)($metrics['mae'] ?? 0), 2)) ?> <?= h($unitLabel) ?></strong>.
          </p>
          <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-mono">
            <span>Formula: &Sigma;|y &minus; &ycirc;| / n</span>
            <span class="text-teal-600 font-semibold">Linear Penalty</span>
          </div>
        </div>

        <!-- RMSE Card -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 relative overflow-hidden group hover:border-teal-400 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold tracking-wider text-slate-500">Root Mean Squared Error</span>
            <span class="text-xs font-mono px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-2xs font-semibold">RMSE</span>
          </div>
          <div class="text-3xl font-extrabold text-slate-900 mt-3 font-mono">
            <?= h(number_format((float)($metrics['rmse'] ?? 0), 2)) ?>
            <span class="text-sm font-normal text-slate-500 font-sans"><?= h($unitLabel) ?></span>
          </div>
          <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Measures residual spread; penalizes large sudden demand spikes or outlier days more heavily.
          </p>
          <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-mono">
            <span>Formula: &radic;(&Sigma;(y &minus; &ycirc;)&sup2; / n)</span>
            <span class="text-teal-600 font-semibold">Quadratic Penalty</span>
          </div>
        </div>

        <!-- MAPE Card -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 relative overflow-hidden group hover:border-teal-400 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold tracking-wider text-slate-500">Mean Absolute % Error</span>
            <span class="text-xs font-mono px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-2xs font-semibold">MAPE</span>
          </div>
          <div class="text-3xl font-extrabold text-slate-900 mt-3 font-mono">
            <?= h(number_format((float)($metrics['mape'] ?? 0), 1)) ?><span class="text-xl font-bold text-slate-500 font-sans">%</span>
          </div>
          <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Relative percentage deviation across non-zero active days (Overall Accuracy: <strong><?= h(number_format(max(0, 100 - (float)($metrics['mape'] ?? 0)), 1)) ?>%</strong>).
          </p>
          <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-mono">
            <span>Formula: &Sigma;(|y &minus; &ycirc;| / y) / n</span>
            <span class="text-teal-600 font-semibold">Scale-Independent</span>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2 bg-white p-4 rounded shadow">
      <div class="text-sm text-slate-500 mb-3">Recent History And Forecast</div>
      <canvas id="forecastChart" height="140"></canvas>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-sm text-slate-500">Planning Notes</div>
      <ul class="mt-3 space-y-3 text-sm text-slate-700">
        <?php foreach ($planningNotes as $note): ?>
          <li class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0"><?= h($note) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Next 7 Days</div>
      <div class="text-2xl font-semibold mt-2"><?= h(number_format($firstWeekAverage, 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1">Average <?= h($unitLabel) ?> per day</div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Rest Of Forecast</div>
      <div class="text-2xl font-semibold mt-2"><?= h(number_format($laterAverage, 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1">Average <?= h($unitLabel) ?> per day</div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2 bg-white p-5 rounded shadow">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Recent Forecast</div>
          <div class="text-lg font-semibold">Next Few Days At A Glance</div>
        </div>
        <div class="text-sm text-slate-500">Upcoming <?= h(count($recentForecastRows)) ?> days</div>
      </div>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($recentForecastRows as $row): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-widest text-slate-400"><?= h($row['date']) ?></div>
            <div class="text-2xl font-semibold mt-2"><?= h(number_format($row['value'], 1)) ?></div>
            <div class="text-sm text-slate-500 mt-1"><?= h($unitLabel) ?> expected</div>
            <div class="text-xs text-slate-400 mt-3">Range: <?= h(number_format($row['lower'], 1)) ?> to <?= h(number_format($row['upper'], 1)) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-sm text-slate-500">Print Notes</div>
      <div class="text-lg font-semibold">Report Summary</div>
      <ul class="mt-4 space-y-3 text-sm text-slate-700">
        <li class="border-b border-slate-100 pb-3">Forecast generated on <?= h($result['generated_on'] ?? date('Y-m-d')) ?>.</li>
        <li class="border-b border-slate-100 pb-3">Series selected: <?= h($seriesOptions[$seriesKey] ?? $seriesKey) ?>.</li>
        <li class="border-b border-slate-100 pb-3">Planning horizon: <?= h($horizon) ?> days.</li>
        <li>Use this report for staffing, medicine preparation, and short-term scheduling.</li>
      </ul>
    </div>
  </div>

  <script>
    const historyRows = <?= json_encode($historyRows) ?>;
    const forecastRows = <?= json_encode($forecastRows) ?>;

    const historyLabels = historyRows.map((row) => row.date);
    const forecastLabels = forecastRows.map((row) => row.date);
    const labels = [...historyLabels, ...forecastLabels];

    const historyValues = historyRows.map((row) => row.value);
    const forecastValues = forecastRows.map((row) => row.value);
    const lowerValues = forecastRows.map((row) => row.lower);
    const upperValues = forecastRows.map((row) => row.upper);
    const lastHistoryValue = historyValues.length ? historyValues[historyValues.length - 1] : null;

    const historyDataset = [...historyValues, ...Array(forecastValues.length).fill(null)];
    const forecastDataset = [...Array(Math.max(historyValues.length - 1, 0)).fill(null), lastHistoryValue, ...forecastValues];
    const lowerDataset = [...Array(historyValues.length).fill(null), ...lowerValues];
    const upperDataset = [...Array(historyValues.length).fill(null), ...upperValues];

    new Chart(document.getElementById('forecastChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Recent actual',
            data: historyDataset,
            borderColor: '#0f172a',
            backgroundColor: 'rgba(15,23,42,0.08)',
            tension: 0.25,
            pointRadius: 1.5,
            borderWidth: 2
          },
          {
            label: 'Forecast range (low)',
            data: lowerDataset,
            borderColor: 'rgba(14,165,164,0)',
            backgroundColor: 'rgba(14,165,164,0.12)',
            pointRadius: 0,
            borderWidth: 0
          },
          {
            label: 'Forecast range (high)',
            data: upperDataset,
            borderColor: 'rgba(14,165,164,0)',
            backgroundColor: 'rgba(14,165,164,0.12)',
            pointRadius: 0,
            borderWidth: 0,
            fill: '-1'
          },
          {
            label: 'Forecast',
            data: forecastDataset,
            borderColor: '#0ea5a4',
            backgroundColor: 'rgba(14,165,164,0.08)',
            tension: 0.3,
            pointRadius: 1.5,
            borderDash: [6, 4],
            borderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' }
        },
        scales: {
          x: {
            ticks: { maxTicksLimit: 10 }
          },
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
<?php endif; ?>

<script>
  (function () {
    if (typeof Swal === 'undefined') return;

    var forecastForm = document.querySelector('form[method="post"]');
    if (!forecastForm) return;

    forecastForm.addEventListener('submit', function () {
      Swal.fire({
        title: 'Generating forecast',
        text: 'Please wait while the forecasting model runs.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function () {
          Swal.showLoading();
        }
      });
    });
  })();
</script>

<script>
  (function () {
    if (typeof Swal === 'undefined') return;

    var forecastError = <?= json_encode($error) ?>;
    var forecastGenerated = <?= json_encode($forecastGenerated) ?>;
    var seriesLabel = <?= json_encode($seriesOptions[$seriesKey] ?? $seriesKey) ?>;
    var horizonValue = <?= json_encode($horizon) ?>;

    if (forecastError) {
      Swal.fire({
        icon: 'error',
        title: 'Forecast failed',
        text: forecastError
      });
      return;
    }

    if (forecastGenerated) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true
      });
      Toast.fire({
        icon: 'success',
        title: `Forecast ready: ${seriesLabel} for ${horizonValue} days`
      });
    }
  })();
</script>

<script>
  const snapshotSeriesLabelMap = <?= json_encode($seriesOptions) ?>;
  const snapshotUnitMap = <?= json_encode($seriesUnits) ?>;
  const snapshotSeriesInput = document.querySelector('select[name="series_key"]');
  const snapshotHorizonInput = document.querySelector('input[name="horizon"]');
  const snapshotIntroEl = document.getElementById('snapshotIntro');
  const snapshotMetaEl = document.getElementById('snapshotMeta');
  const snapshotAverageEl = document.getElementById('snapshotAverage');
  const snapshotUnitEl = document.getElementById('snapshotUnit');
  const snapshotPeakEl = document.getElementById('snapshotPeak');
  const snapshotPeakDateEl = document.getElementById('snapshotPeakDate');
  const snapshotTotalEl = document.getElementById('snapshotTotal');
  const snapshotHorizonEl = document.getElementById('snapshotHorizon');
  const snapshotMapeEl = document.getElementById('snapshotMape');
  const snapshotMaeEl = document.getElementById('snapshotMae');
  const snapshotRatingBadgeEl = document.getElementById('snapshotRatingBadge');
  const snapshotDaysEl = document.getElementById('snapshotDays');
  const snapshotSummaryEl = document.getElementById('snapshotSummary');

  function renderSnapshotError(message) {
    snapshotIntroEl.textContent = message;
    snapshotAverageEl.textContent = '--';
    snapshotUnitEl.textContent = '--';
    snapshotPeakEl.textContent = '--';
    snapshotPeakDateEl.textContent = '--';
    snapshotTotalEl.textContent = '--';
    snapshotHorizonEl.textContent = '--';
    if (snapshotMapeEl) snapshotMapeEl.textContent = '--';
    if (snapshotMaeEl) snapshotMaeEl.textContent = '--';
    if (snapshotRatingBadgeEl) {
      snapshotRatingBadgeEl.textContent = 'Fit';
      snapshotRatingBadgeEl.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200';
    }
    snapshotDaysEl.innerHTML = '';
    snapshotSummaryEl.innerHTML = '<li>Forecast snapshot is not available right now.</li>';
  }

  function renderSnapshot(data, seriesKey, horizon) {
    const summary = data.summary || {};
    const metrics = data.metrics || summary;
    const rows = (data.forecast || []).slice(0, 6);
    const unit = snapshotUnitMap[seriesKey] || 'items';
    const label = snapshotSeriesLabelMap[seriesKey] || seriesKey;

    snapshotIntroEl.textContent = summary.intro || 'Quick forecast ready.';
    snapshotMetaEl.textContent = `${label} for the next ${horizon} days`;
    snapshotAverageEl.textContent = Number(summary.forecast_average || 0).toFixed(1);
    snapshotUnitEl.textContent = `${unit} expected each day`;
    snapshotPeakEl.textContent = Number(summary.peak_value || 0).toFixed(1);
    snapshotPeakDateEl.textContent = summary.peak_date || '--';
    snapshotTotalEl.textContent = Number(summary.expected_total || 0).toFixed(1);
    snapshotHorizonEl.textContent = `Across ${horizon} days`;

    if (snapshotMapeEl) {
      snapshotMapeEl.textContent = (metrics.mape !== undefined && metrics.mape !== null) ? `${Number(metrics.mape).toFixed(1)}%` : '--';
    }
    if (snapshotMaeEl) {
      snapshotMaeEl.textContent = (metrics.mae !== undefined && metrics.mae !== null) ? `MAE: ±${Number(metrics.mae).toFixed(1)} ${unit}` : '--';
    }
    if (snapshotRatingBadgeEl && metrics.mape !== undefined && metrics.mape !== null) {
      const m = Number(metrics.mape);
      const rating = metrics.accuracy_rating || (m < 10 ? 'High Accuracy' : (m < 20 ? 'Good Fit' : (m < 50 ? 'Reasonable' : 'High Variance')));
      snapshotRatingBadgeEl.textContent = rating;
      snapshotRatingBadgeEl.className = `text-[10px] font-bold px-2 py-0.5 rounded border ${m < 20 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (m < 50 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200')}`;
    }

    snapshotDaysEl.innerHTML = rows.map((row) => `
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">${row.date}</div>
        <div class="text-2xl font-semibold mt-2">${Number(row.value).toFixed(1)}</div>
        <div class="text-sm text-slate-500 mt-1">${unit} expected</div>
        <div class="text-xs text-slate-400 mt-3">Range: ${Number(row.lower).toFixed(1)} to ${Number(row.upper).toFixed(1)}</div>
      </div>
    `).join('');

    snapshotSummaryEl.innerHTML = [
      `Forecast generated on ${data.generated_on || '--'}.`,
      `Series selected: ${label}.`,
      `Planning horizon: ${horizon} days.`,
      'Use this snapshot for quick planning before running the full forecast report.'
    ].map((line) => `<li class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0">${line}</li>`).join('');
  }

  function loadSnapshot() {
    const seriesKey = snapshotSeriesInput.value;
    const horizon = snapshotHorizonInput.value || '30';
    const body = new URLSearchParams({ series_key: seriesKey, horizon, fast: '1' });

    fetch('/HealthLogs/public/forecast_run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error || !data.summary || !data.forecast) {
          throw new Error(data.error || 'Snapshot unavailable');
        }
        renderSnapshot(data, seriesKey, horizon);
      })
      .catch(() => {
        renderSnapshotError('Forecast snapshot is not ready yet.');
      });
  }

  snapshotSeriesInput.addEventListener('change', loadSnapshot);
  snapshotHorizonInput.addEventListener('change', loadSnapshot);
  loadSnapshot();
</script>

<script>
  const seasonalDiseaseRows = <?= json_encode($seasonalDiseaseRows) ?>;
  const diseaseTrendMonths = <?= json_encode($diseaseTrendMonths) ?>;
  const diseaseTrendSeries = <?= json_encode($diseaseTrendSeries) ?>;

  if (seasonalDiseaseRows.length) {
    new Chart(document.getElementById('seasonalDiseaseChart'), {
      type: 'bar',
      data: {
        labels: seasonalDiseaseRows.map((row) => row.month_label),
        datasets: [{
          label: 'Cases',
          data: seasonalDiseaseRows.map((row) => Number(row.total_cases)),
          backgroundColor: 'rgba(14,165,164,0.6)',
          borderColor: '#0ea5a4',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  const trendDiseaseNames = Object.keys(diseaseTrendSeries || {});
  if (diseaseTrendMonths.length && trendDiseaseNames.length) {
    const palette = ['#0ea5a4', '#2563eb', '#9333ea', '#f97316', '#16a34a'];
    const datasets = trendDiseaseNames.map((name, idx) => ({
      label: name,
      data: diseaseTrendSeries[name],
      borderColor: palette[idx % palette.length],
      backgroundColor: palette[idx % palette.length],
      tension: 0.25,
      pointRadius: 2,
      borderWidth: 2
    }));

    new Chart(document.getElementById('diseaseTrendChart'), {
      type: 'line',
      data: { labels: diseaseTrendMonths, datasets },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  // Model Log Modal Controls
  const modelLogModal = document.getElementById('modelLogModal');
  const modelLogModalBackdrop = document.getElementById('modelLogModalBackdrop');
  const modelLogModalClose = document.getElementById('modelLogModalClose');
  const modelLogModalCloseBtn = document.getElementById('modelLogModalCloseBtn');
  
  function openModelLogModal() {
    if (modelLogModal) modelLogModal.classList.remove('hidden');
  }

  function closeModelLogModal() {
    if (modelLogModal) modelLogModal.classList.add('hidden');
  }

  if (modelLogModalBackdrop) modelLogModalBackdrop.addEventListener('click', closeModelLogModal);
  if (modelLogModalClose) modelLogModalClose.addEventListener('click', closeModelLogModal);
  if (modelLogModalCloseBtn) modelLogModalCloseBtn.addEventListener('click', closeModelLogModal);
  
  // Show error messages using sweetalert
  document.querySelectorAll('.view-run-error-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const errorMsg = this.getAttribute('data-error-msg') || 'Unknown error occurred.';
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Model Execution Failed',
          text: errorMsg,
          confirmButtonColor: '#0f172a'
        });
      } else {
        alert("Model Execution Failed:\n" + errorMsg);
      }
    });
  });

  // Fetch and show run details
  document.querySelectorAll('.view-run-details-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const runId = this.getAttribute('data-run-id');
      if (!runId) return;
      
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Loading Model Details',
          text: 'Retrieving parameters and results from database...',
          allowOutsideClick: false,
          showConfirmButton: false,
          didOpen: () => Swal.showLoading()
        });
      }
      
      fetch(`/HealthLogs/public/forecast_run_details.php?run_id=${runId}`)
        .then(res => {
          if (!res.ok) throw new Error('Failed to fetch details');
          return res.json();
        })
        .then(data => {
          if (typeof Swal !== 'undefined') Swal.close();
          
          // Populate run info
          const seriesName = data.run.series_key === 'visits_total' ? 'Patient Visits' : 'Medicine Demand';
          document.getElementById('modalTitle').textContent = `Forecast Details - ${seriesName}`;
          document.getElementById('modalSubtitle').textContent = `Run ID: #${data.run.id} | Generated on ${data.run.created_at}`;
          document.getElementById('modalModelType').textContent = data.run.model_type === 'ARIMA' ? 'ARIMA (Auto-fit)' : 'Fast Average';
          document.getElementById('modalExecTime').textContent = `${data.run.execution_time_seconds || '0.000'} seconds`;
          document.getElementById('modalHistorySize').textContent = data.run.history_points ? `${data.run.history_points} days` : 'N/A';
          document.getElementById('modalTrainingPoints').textContent = data.run.training_points ? `${data.run.training_points} days` : 'N/A';
          
          // Populate accuracy & error metrics
          const maeVal = data.run.mae || (data.parameters && data.parameters.mae);
          const rmseVal = data.run.rmse || (data.parameters && data.parameters.rmse);
          const mapeVal = data.run.mape || (data.parameters && data.parameters.mape);
          const metricUnit = data.run.series_key === 'visits_total' ? 'visits' : 'units';
          
          const modalMaeEl = document.getElementById('modalMae');
          const modalRmseEl = document.getElementById('modalRmse');
          const modalMapeEl = document.getElementById('modalMape');
          const modalRatingEl = document.getElementById('modalAccuracyRating');
          
          if (modalMaeEl) modalMaeEl.textContent = (maeVal !== null && maeVal !== undefined) ? `${Number(maeVal).toFixed(2)} ${metricUnit}` : 'N/A';
          if (modalRmseEl) modalRmseEl.textContent = (rmseVal !== null && rmseVal !== undefined) ? `${Number(rmseVal).toFixed(2)} ${metricUnit}` : 'N/A';
          if (modalMapeEl) modalMapeEl.textContent = (mapeVal !== null && mapeVal !== undefined) ? `${Number(mapeVal).toFixed(1)}%` : 'N/A';
          
          if (modalRatingEl) {
            if (mapeVal !== null && mapeVal !== undefined) {
              const m = Number(mapeVal);
              const rating = m < 10 ? 'High Accuracy' : (m < 20 ? 'Good Fit' : (m < 50 ? 'Reasonable' : 'High Variance'));
              modalRatingEl.textContent = rating;
              modalRatingEl.className = `px-2.5 py-0.5 rounded text-xs font-semibold border ${m < 20 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (m < 50 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200')}`;
            } else {
              modalRatingEl.textContent = 'Statistical Fit';
              modalRatingEl.className = 'px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200';
            }
          }
          
          // Calculate insights
          let total = 0;
          let peakVal = -1;
          let peakDate = '';
          let quietVal = Infinity;
          let quietDate = '';
          let lowerSum = 0;
          let upperSum = 0;
          
          if (data.results && data.results.length > 0) {
            data.results.forEach(res => {
              const val = Number(res.forecast_value);
              total += val;
              lowerSum += Number(res.lower_bound);
              upperSum += Number(res.upper_bound);
              
              if (val > peakVal) {
                peakVal = val;
                peakDate = res.forecast_date;
              }
              if (val < quietVal) {
                quietVal = val;
                quietDate = res.forecast_date;
              }
            });
            
            const avg = total / data.results.length;
            const avgLower = lowerSum / data.results.length;
            const avgUpper = upperSum / data.results.length;
            const unit = data.run.series_key === 'visits_total' ? 'visits' : 'units';
            
            document.getElementById('modalInsightAvg').textContent = `${avg.toFixed(1)} ${unit} / day`;
            document.getElementById('modalInsightTotal').textContent = `${total.toFixed(0)} ${unit}`;
            document.getElementById('modalInsightHorizon').textContent = `${data.results.length} days`;
            document.getElementById('modalInsightPeak').textContent = `${peakVal.toFixed(1)} ${unit} (on ${peakDate})`;
            document.getElementById('modalInsightQuiet').textContent = `${quietVal.toFixed(1)} ${unit} (on ${quietDate})`;
            document.getElementById('modalInsightRange').textContent = `${avgLower.toFixed(1)} to ${avgUpper.toFixed(1)} ${unit}`;
          }
          
          // Render predictions table
          const resultsBody = document.getElementById('modalPredictionsBody');
          resultsBody.innerHTML = '';
          
          if (data.results && data.results.length > 0) {
            data.results.forEach(res => {
              const row = document.createElement('tr');
              row.className = 'hover:bg-slate-50/50 transition-colors';
              row.innerHTML = `
                <td class="py-2 px-4 font-semibold text-slate-600 font-mono">${res.forecast_date}</td>
                <td class="py-2 px-4 text-right font-semibold text-slate-900 font-mono">${Number(res.forecast_value).toFixed(1)}</td>
                <td class="py-2 px-4 text-right text-slate-500 font-mono">${Number(res.lower_bound).toFixed(1)}</td>
                <td class="py-2 px-4 text-right text-slate-500 font-mono">${Number(res.upper_bound).toFixed(1)}</td>
              `;
              resultsBody.appendChild(row);
            });
          } else {
            resultsBody.innerHTML = `<tr><td colspan="4" class="py-4 px-4 text-center text-slate-400">No predictions recorded.</td></tr>`;
          }
          
          openModelLogModal();
        })
        .catch(err => {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error Loading Details',
              text: err.message
            });
          } else {
            alert('Error loading details: ' + err.message);
          }
        });
    });
  });

  // Log Filter Tab Switching (Successful by default)
  const logFilterBtns = document.querySelectorAll('.log-filter-btn');
  const logRows = document.querySelectorAll('.log-row');
  const noFilteredLogsRow = document.getElementById('noFilteredLogsRow');

  function applyLogFilter(filter) {
    let visibleCount = 0;
    logRows.forEach(row => {
      const status = row.getAttribute('data-status');
      if (filter === 'all' || status === filter) {
        row.classList.remove('hidden');
        visibleCount++;
      } else {
        row.classList.add('hidden');
      }
    });

    if (noFilteredLogsRow) {
      if (visibleCount === 0) {
        noFilteredLogsRow.classList.remove('hidden');
      } else {
        noFilteredLogsRow.classList.add('hidden');
      }
    }

    logFilterBtns.forEach(btn => {
      const isSelected = btn.getAttribute('data-filter') === filter;
      if (isSelected) {
        btn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'font-semibold');
        btn.classList.remove('text-slate-600');
      } else {
        btn.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'font-semibold');
        btn.classList.add('text-slate-600');
      }
    });
  }

  logFilterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      applyLogFilter(this.getAttribute('data-filter'));
    });
  });

  // Set default view to 'success'
  applyLogFilter('success');
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
