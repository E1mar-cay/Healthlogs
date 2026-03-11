<?php
$pageTitle = 'ARIMA Forecasting';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$seriesOptions = [
    'visits_total' => 'Patient Visits (Total)',
    'medicine_total' => 'Medicine Demand (Total)',
];

$seriesKey = $_POST['series_key'] ?? 'visits_total';
$horizon = (int)($_POST['horizon'] ?? 30);

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
        $error = 'No output from ARIMA script.';
    } else {
        $data = json_decode($output, true);
        if (!is_array($data)) {
            $error = $output;
        } else {
            $result = $data;
        }
    }
}
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">ARIMA Forecasting</div>
      <div class="text-2xl font-semibold">Forecast Runner</div>
      <p class="text-sm text-slate-500 mt-1">Generate daily projections for demand and visit volume.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Predictive Insights</span>
      <span class="app-chip">Planning</span>
    </div>
  </div>

  <form class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4" method="post">
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">Series</label>
      <select name="series_key" class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($seriesOptions as $key => $label): ?>
          <option value="<?= h($key) ?>" <?= $seriesKey === $key ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Horizon (days)</label>
      <input name="horizon" value="<?= h($horizon) ?>" type="number" class="mt-1 w-full border rounded px-3 py-2" />
    </div>
    <div class="md:col-span-3">
      <button class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow" type="submit">Run Forecast</button>
    </div>
  </form>

  <?php if ($error): ?>
    <div class="mt-4 text-sm text-red-600">Error: <?= h($error) ?></div>
  <?php endif; ?>
</div>

<?php if ($result): ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Total Forecast</div>
      <div class="text-2xl font-semibold mt-2" id="totalForecast">--</div>
      <div class="text-sm text-slate-500 mt-1">Across <?= h($horizon) ?> days</div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Average / Day</div>
      <div class="text-2xl font-semibold mt-2" id="avgForecast">--</div>
      <div class="text-sm text-slate-500 mt-1">Smoothed baseline</div>
    </div>
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Peak Value</div>
      <div class="text-2xl font-semibold mt-2" id="peakForecast">--</div>
      <div class="text-sm text-slate-500 mt-1" id="peakDate">--</div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
    <div class="bg-white p-4 rounded shadow">
      <div class="text-sm text-slate-500 mb-3">Forecast Trend</div>
      <canvas id="forecastChart" height="140"></canvas>
    </div>
    <div class="bg-white p-4 rounded shadow">
      <div class="text-sm text-slate-500 mb-3">Daily Change</div>
      <canvas id="deltaChart" height="140"></canvas>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
    <div class="bg-white p-4 rounded shadow">
      <div class="text-sm text-slate-500 mb-3">Cumulative Forecast</div>
      <canvas id="cumulativeChart" height="140"></canvas>
    </div>
    <div class="bg-white p-4 rounded shadow">
      <div class="text-sm text-slate-500 mb-3">7-Day Moving Average</div>
      <canvas id="movingAvgChart" height="140"></canvas>
    </div>
  </div>

  <div class="bg-white p-4 rounded shadow overflow-x-auto mt-6">
    <div class="text-sm text-slate-500 mb-3">Forecast Table</div>
    <table class="min-w-full text-sm">
      <thead class="text-slate-600">
        <tr>
          <th class="text-left px-3 py-2">Date</th>
          <th class="text-left px-3 py-2">Value</th>
          <th class="text-left px-3 py-2">Daily Change</th>
          <th class="text-left px-3 py-2">Cumulative</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['forecast'] as $index => $row): ?>
          <tr class="border-t">
            <td class="px-3 py-2"><?= h($row['date']) ?></td>
            <td class="px-3 py-2"><?= h($row['value']) ?></td>
            <td class="px-3 py-2"><?= $index === 0 ? '-' : h($row['value'] - $result['forecast'][$index - 1]['value']) ?></td>
            <td class="px-3 py-2"><?= h(array_sum(array_column(array_slice($result['forecast'], 0, $index + 1), 'value'))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    const labels = <?= json_encode(array_column($result['forecast'], 'date')) ?>;
    const values = <?= json_encode(array_column($result['forecast'], 'value')) ?>;

    const deltas = values.map((value, index) => {
      if (index === 0) return 0;
      return value - values[index - 1];
    });
    const cumulative = values.reduce((acc, value, index) => {
      const total = (acc[index - 1] || 0) + value;
      acc.push(total);
      return acc;
    }, []);
    const movingAverage = values.map((_, index) => {
      const start = Math.max(0, index - 6);
      const slice = values.slice(start, index + 1);
      return slice.reduce((sum, value) => sum + value, 0) / slice.length;
    });

    const totalForecast = values.reduce((sum, value) => sum + value, 0);
    const avgForecast = totalForecast / values.length;
    const peakValue = Math.max(...values);
    const peakIndex = values.indexOf(peakValue);

    document.getElementById('totalForecast').textContent = totalForecast.toFixed(1);
    document.getElementById('avgForecast').textContent = avgForecast.toFixed(1);
    document.getElementById('peakForecast').textContent = peakValue.toFixed(1);
    document.getElementById('peakDate').textContent = labels[peakIndex];

    new Chart(document.getElementById('forecastChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Forecast',
          data: values,
          borderColor: '#0f172a',
          backgroundColor: 'rgba(15,23,42,0.12)',
          tension: 0.3,
          fill: true,
          pointRadius: 2
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('deltaChart'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Daily Change',
          data: deltas,
          backgroundColor: deltas.map((value) => value >= 0 ? 'rgba(14,165,164,0.5)' : 'rgba(239,68,68,0.5)'),
          borderColor: deltas.map((value) => value >= 0 ? 'rgba(14,165,164,1)' : 'rgba(239,68,68,1)'),
          borderWidth: 1
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('cumulativeChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Cumulative',
          data: cumulative,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37,99,235,0.12)',
          tension: 0.25,
          fill: true,
          pointRadius: 2
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('movingAvgChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: '7-Day Avg',
          data: movingAverage,
          borderColor: '#0ea5a4',
          backgroundColor: 'rgba(14,165,164,0.12)',
          tension: 0.35,
          fill: true,
          pointRadius: 0
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });
  </script>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
