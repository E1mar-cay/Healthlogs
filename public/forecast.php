<?php
$pageTitle = 'Forecasting';
require __DIR__ . '/partials/bootstrap.php';
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
    $python = 'C:\\Users\\Elmar Cayaba\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
    $script = __DIR__ . '/../scripts/forecast_arima.py';
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) .
        ' --series-key ' . escapeshellarg($seriesKey) .
        ' --horizon ' . escapeshellarg((string)$horizon) .
        ' 2>&1';
    $output = shell_exec($cmd);

    if (!$output) {
        $error = 'No output from forecasting script.';
    } else {
        $data = json_decode($output, true);
        if (!is_array($data)) {
            $error = 'Forecasting failed. Please try again.';
        } elseif (!empty($data['error'])) {
            $error = $data['error'];
        } elseif (!isset($data['forecast'], $data['summary'])) {
            $error = 'Forecast response is incomplete.';
        } else {
            $result = $data;
        }
    }
}

$summary = $result['summary'] ?? null;
$forecastRows = $result['forecast'] ?? [];
$historyRows = $result['history'] ?? [];
$unitLabel = $seriesUnits[$seriesKey] ?? 'items';

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

  <?php if ($error): ?>
    <div class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <?= h($error) ?>
    </div>
  <?php endif; ?>
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

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
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
    snapshotDaysEl.innerHTML = '';
    snapshotSummaryEl.innerHTML = '<li>Forecast snapshot is not available right now.</li>';
  }

  function renderSnapshot(data, seriesKey, horizon) {
    const summary = data.summary || {};
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

<?php require __DIR__ . '/partials/footer.php'; ?>
