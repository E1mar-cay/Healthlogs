<?php
$pageTitle = 'Administrator Dashboard';
require __DIR__ . '/../partials/header.php';

$selectedVisitMonth = trim((string)($_GET['visit_month'] ?? ''));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedVisitMonth)) {
    $selectedVisitMonth = date('Y-m');
}
$isCurrentVisitMonth = ($selectedVisitMonth === date('Y-m'));
$visitMonthStart = $selectedVisitMonth . '-01';
$visitMonthEndLastDay = date('Y-m-t', strtotime($visitMonthStart));
// For the current month, cap at today's date so future scheduled dates are never counted as completed visits
$visitMonthEnd = ($isCurrentVisitMonth && date('Y-m-d') < $visitMonthEndLastDay)
    ? date('Y-m-d')
    : $visitMonthEndLastDay;
$selectedMonthLabel = date('F Y', strtotime($visitMonthStart));

$adminStats = [
    'total_patients' => 0,
    'monthly_visits' => 0,
    'low_stock_items' => 0,
    'active_users' => 0,
    'pending_reminders' => 0,
    'ongoing_pregnancies' => 0,
    'ncd_clients' => 0,
];
$visitBreakdown = [
    'general' => 0,
    'prenatal' => 0,
    'postnatal' => 0,
    'fp' => 0,
    'ncd' => 0,
    'immunization' => 0,
];

try {
    $adminStats['total_patients'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM patients WHERE status = 'active'"
    )->fetchColumn();

    $vStmt = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM visits WHERE DATE(visit_datetime) BETWEEN ? AND ?) AS general_cnt,
            (SELECT COUNT(*) FROM prenatal_visits WHERE DATE(visit_datetime) BETWEEN ? AND ?) AS prenatal_cnt,
            (SELECT COUNT(*) FROM postnatal_visits WHERE DATE(visit_datetime) BETWEEN ? AND ?) AS postnatal_cnt,
            (SELECT COUNT(*) FROM fp_visits WHERE DATE(visit_date) BETWEEN ? AND ?) AS fp_cnt,
            (SELECT COUNT(*) FROM ncd_visits WHERE DATE(visit_date) BETWEEN ? AND ?) AS ncd_cnt,
            (SELECT COUNT(*) FROM immunization_records WHERE DATE(administered_on) BETWEEN ? AND ?) AS imm_cnt
    ");
    $vStmt->execute([
        $visitMonthStart, $visitMonthEnd,
        $visitMonthStart, $visitMonthEnd,
        $visitMonthStart, $visitMonthEnd,
        $visitMonthStart, $visitMonthEnd,
        $visitMonthStart, $visitMonthEnd,
        $visitMonthStart, $visitMonthEnd,
    ]);
    $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
    if ($vRow) {
        $visitBreakdown['general'] = (int)$vRow['general_cnt'];
        $visitBreakdown['prenatal'] = (int)$vRow['prenatal_cnt'];
        $visitBreakdown['postnatal'] = (int)$vRow['postnatal_cnt'];
        $visitBreakdown['fp'] = (int)$vRow['fp_cnt'];
        $visitBreakdown['ncd'] = (int)$vRow['ncd_cnt'];
        $visitBreakdown['immunization'] = (int)$vRow['imm_cnt'];
        $adminStats['monthly_visits'] = array_sum($visitBreakdown);
    }

    $adminStats['low_stock_items'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM (
             SELECT m.id
             FROM medicines m
             LEFT JOIN (
                 SELECT mb.medicine_id, SUM(mt.quantity) AS valid_stock
                 FROM medicine_batches mb
                 JOIN medicine_transactions mt ON mt.batch_id = mb.id
                 WHERE mb.expiry_date >= CURDATE()
                 GROUP BY mb.medicine_id
             ) stk ON stk.medicine_id = m.id
             GROUP BY m.id, m.reorder_level
             HAVING COALESCE(MAX(stk.valid_stock), 0) <= COALESCE(m.reorder_level, 0)
         ) low_stock"
    )->fetchColumn();

    $adminStats['active_users'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE status = 'active'"
    )->fetchColumn();

    $adminStats['pending_reminders'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM reminders
         WHERE status = 'pending'
           AND due_date <= CURDATE()"
    )->fetchColumn();

    $adminStats['ongoing_pregnancies'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM pregnancies WHERE status = 'ongoing'"
    )->fetchColumn();

    $adminStats['ncd_clients'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM ncd_records WHERE status IN ('active', 'controlled', 'uncontrolled')"
    )->fetchColumn();
} catch (Throwable $e) {
    // Keep dashboard usable even if a summary query fails.
}
?>

<div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 border-t-4 border-t-teal-600 mb-5">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200 mb-1.5">
        <i class="fas fa-user-shield text-[10px]"></i>
        <span>Welcome back, <?= h($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
      </div>
      <div class="text-2xl font-bold text-slate-900">Administrator Dashboard</div>
      <p class="text-sm text-slate-500 mt-0.5">System overview, user management, program analytics, and operational controls.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip"><i class="fas fa-shield-halved text-teal-600"></i> Admin Access</span>
      <a href="/HealthLogs/public/patients/form.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold shadow-2xs transition">
        <i class="fas fa-user-plus text-[11px]"></i>
        <span>New Patient</span>
      </a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
  <div class="bg-white p-5 rounded-2xl border border-slate-200 border-l-4 border-l-teal-500">
    <div class="text-xs font-bold uppercase tracking-wider text-teal-700">Patients</div>
    <div class="text-3xl font-extrabold text-slate-900 mt-1.5 font-mono"><?= h(number_format($adminStats['total_patients'])) ?></div>
    <div class="text-xs text-slate-500 mt-1 font-medium">Total active registered</div>
  </div>
  <div class="bg-white p-5 rounded-2xl border border-slate-200 border-l-4 border-l-indigo-500">
    <div class="flex items-center justify-between gap-2">
      <div class="text-xs font-bold uppercase tracking-wider text-indigo-700">Visits</div>
      <form method="get" class="inline-block">
        <select name="visit_month" onchange="this.form.submit()" aria-label="Select visits month" class="text-[11px] font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-2 py-0.5 focus:outline-none focus:ring-2 focus:ring-teal-500">
          <?php
            for ($mIdx = 0; $mIdx < 12; $mIdx++) {
                $mTs = strtotime("first day of -$mIdx month");
                $mVal = date('Y-m', $mTs);
                $mText = date('M Y', $mTs) . ($mIdx === 0 ? ' (This Month)' : '');
                $mSel = ($selectedVisitMonth === $mVal) ? 'selected' : '';
                echo "<option value=\"" . h($mVal) . "\" $mSel>" . h($mText) . "</option>";
            }
          ?>
        </select>
      </form>
    </div>
    <div class="text-3xl font-extrabold text-slate-900 mt-1.5 font-mono"><?= h(number_format($adminStats['monthly_visits'])) ?></div>
    <div class="text-xs text-slate-500 mt-1 font-medium">
      <?= $isCurrentVisitMonth ? 'This month (' . h($selectedMonthLabel) . ')' : h($selectedMonthLabel) ?>
    </div>
    <?php
      $bdParts = [];
      if ($visitBreakdown['immunization'] > 0) $bdParts[] = 'Bakuna: ' . number_format($visitBreakdown['immunization']);
      if (($visitBreakdown['prenatal'] + $visitBreakdown['postnatal']) > 0) $bdParts[] = 'Maternal: ' . number_format($visitBreakdown['prenatal'] + $visitBreakdown['postnatal']);
      if ($visitBreakdown['fp'] > 0) $bdParts[] = 'FP: ' . number_format($visitBreakdown['fp']);
      if ($visitBreakdown['ncd'] > 0) $bdParts[] = 'NCD: ' . number_format($visitBreakdown['ncd']);
      if ($visitBreakdown['general'] > 0) $bdParts[] = 'Consult: ' . number_format($visitBreakdown['general']);
    ?>
    <?php if (!empty($bdParts)): ?>
      <div class="text-[11px] text-slate-400 mt-1 truncate" title="<?= h(implode(' • ', $bdParts)) ?>">
        <?= h(implode(' • ', $bdParts)) ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="bg-white p-5 rounded-2xl border border-slate-200 border-l-4 border-l-purple-500">
    <div class="text-xs font-bold uppercase tracking-wider text-purple-700">NCD Clients</div>
    <div class="text-3xl font-extrabold mt-1.5 text-slate-900 font-mono"><?= h(number_format($adminStats['ncd_clients'])) ?></div>
    <div class="text-xs text-slate-500 mt-1 font-medium">HPN, Diabetes &amp; CKD</div>
  </div>
  <div class="bg-white p-5 rounded-2xl border border-slate-200 border-l-4 <?= $adminStats['low_stock_items'] > 0 ? 'border-l-amber-500' : 'border-l-emerald-500' ?>">
    <div class="text-xs font-bold uppercase tracking-wider <?= $adminStats['low_stock_items'] > 0 ? 'text-amber-700' : 'text-emerald-700' ?>">Low Stock</div>
    <div class="text-3xl font-extrabold text-slate-900 mt-1.5 font-mono"><?= h(number_format($adminStats['low_stock_items'])) ?></div>
    <div class="text-xs text-slate-500 mt-1 font-medium">Items below reorder</div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mt-4">
  <div class="bg-white p-5 rounded-2xl border border-slate-200">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Accounts</div>
    <div class="text-2xl font-bold text-slate-900 mt-1 font-mono"><?= h(number_format($adminStats['active_users'])) ?></div>
    <div class="text-xs text-slate-500 mt-0.5">Active clinical user accounts</div>
  </div>
  <div class="bg-white p-5 rounded-2xl border border-slate-200">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Reminders</div>
    <div class="text-2xl font-bold text-slate-900 mt-1 font-mono"><?= h(number_format($adminStats['pending_reminders'])) ?></div>
    <div class="text-xs text-slate-500 mt-0.5">Pending SMS outreach due now</div>
  </div>
  <div class="bg-white p-5 rounded-2xl border border-slate-200 sm:col-span-2 xl:col-span-1">
    <div class="text-xs font-bold uppercase tracking-wider text-pink-700">Maternal</div>
    <div class="text-2xl font-bold text-slate-900 mt-1 font-mono"><?= h(number_format($adminStats['ongoing_pregnancies'])) ?></div>
    <div class="text-xs text-slate-500 mt-0.5">Ongoing tracked pregnancies</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
  <div class="bg-white p-5 rounded-2xl border border-slate-200 xl:col-span-2">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-xs font-bold uppercase tracking-wider text-teal-700">Quick Links</div>
        <div class="text-lg font-bold text-slate-900">Program Modules</div>
      </div>
      <a class="text-teal-700 hover:text-teal-800 text-xs font-bold bg-teal-50 hover:bg-teal-100 px-3 py-1.5 rounded-xl border border-teal-200 transition" href="/HealthLogs/public/forecast.php">View Forecast &rarr;</a>
    </div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <a href="/HealthLogs/public/immunization/records/index.php" class="p-4 rounded-xl bg-white border border-slate-200 hover:bg-teal-50/40 transition group">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-teal-700 font-bold">
          <i class="fas fa-syringe"></i>
          <span>Immunization</span>
        </div>
        <div class="text-sm font-bold text-slate-900 mt-1.5 group-hover:text-teal-800">Child Vaccines</div>
        <p class="text-slate-500 text-xs mt-1">Schedules, records &amp; coverage.</p>
      </a>
      <a href="/HealthLogs/public/maternal.php" class="p-4 rounded-xl bg-white border border-slate-200 hover:bg-pink-50/40 transition group">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-pink-700 font-bold">
          <i class="fas fa-person-pregnant"></i>
          <span>Maternal Health</span>
        </div>
        <div class="text-sm font-bold text-slate-900 mt-1.5 group-hover:text-pink-800"><?= number_format($adminStats['ongoing_pregnancies']) ?> Ongoing</div>
        <p class="text-slate-500 text-xs mt-1">Prenatal &amp; postnatal TCL care.</p>
      </a>
      <a href="/HealthLogs/public/ncd/records/index.php" class="p-4 rounded-xl bg-white border border-slate-200 hover:bg-indigo-50/40 transition group">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-indigo-700 font-bold">
          <i class="fas fa-heart-pulse"></i>
          <span>NCD / PhilPEN</span>
        </div>
        <div class="text-sm font-bold text-slate-900 mt-1.5 group-hover:text-indigo-800"><?= number_format($adminStats['ncd_clients']) ?> Active</div>
        <p class="text-slate-500 text-xs mt-1">HPN &amp; Diabetes monitoring.</p>
      </a>
      <a href="/HealthLogs/public/inventory.php" class="p-4 rounded-xl bg-white border border-slate-200 hover:bg-amber-50/40 transition group">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-amber-700 font-bold">
          <i class="fas fa-pills"></i>
          <span>Medicine Inventory</span>
        </div>
        <div class="text-sm font-bold text-slate-900 mt-1.5 group-hover:text-amber-800"><?= $adminStats['low_stock_items'] > 0 ? $adminStats['low_stock_items'] . ' Low Stock' : 'Stocked' ?></div>
        <p class="text-slate-500 text-xs mt-1">Batches, expiry &amp; dispensing.</p>
      </a>
      <a href="/HealthLogs/public/family_planning.php" class="p-4 rounded-xl bg-white border border-slate-200 hover:bg-emerald-50/40 transition group">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-emerald-700 font-bold">
          <i class="fas fa-people-roof"></i>
          <span>Family Planning</span>
        </div>
        <div class="text-sm font-bold text-slate-900 mt-1.5 group-hover:text-emerald-800">FP Clients</div>
        <p class="text-slate-500 text-xs mt-1">Contraceptive users &amp; refills.</p>
      </a>
      <a href="/HealthLogs/public/reports.php" class="p-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 transition group">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-700 font-bold">
          <i class="fas fa-file-waveform"></i>
          <span>Reports</span>
        </div>
        <div class="text-sm font-bold text-slate-900 mt-1.5">TCL &amp; Analytics</div>
        <p class="text-slate-500 text-xs mt-1">Program registers &amp; exports.</p>
      </a>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200">
    <div class="text-xs font-bold uppercase tracking-wider text-teal-700">Quick Actions</div>
    <div class="text-lg font-bold text-slate-900">Administrator Tools</div>
    <div class="mt-4 space-y-2.5">
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-teal-50/50 hover:border-teal-300 font-medium text-xs transition" href="/HealthLogs/public/users.php">
        <span><i class="fas fa-user-doctor mr-2.5 text-teal-600"></i>Manage Clinical Staff</span>
        <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
      </a>
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-teal-50/50 hover:border-teal-300 font-medium text-xs transition" href="/HealthLogs/public/inventory.php">
        <span><i class="fas fa-pills mr-2.5 text-amber-600"></i>Review Pharmacy Inventory</span>
        <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
      </a>
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-teal-50/50 hover:border-teal-300 font-medium text-xs transition" href="/HealthLogs/public/reminders.php">
        <span><i class="fas fa-bell mr-2.5 text-indigo-600"></i>Review SMS Reminders</span>
        <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
      </a>
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-teal-50/50 hover:border-teal-300 font-medium text-xs transition" href="/HealthLogs/public/forecast.php">
        <span><i class="fas fa-chart-line mr-2.5 text-emerald-600"></i>Run SARIMA &amp; Stock Forecast</span>
        <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
      </a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200">
    <div class="text-sm text-slate-500">Alerts</div>
    <div class="text-lg font-semibold text-slate-900">Needs Attention</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-2">
      <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span><?= h((string)$adminStats['low_stock_items']) ?> medicine items below reorder level.</li>
      <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span><?= h((string)$adminStats['pending_reminders']) ?> pending reminders due for dispatch.</li>
      <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span><?= h((string)$adminStats['ongoing_pregnancies']) ?> active maternal health ongoing pregnancies.</li>
    </ul>
  </div>
  <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200">
    <div class="text-sm text-slate-500">Forecast Snapshot</div>
    <div class="text-lg font-semibold text-slate-900">Patient Visits</div>
    <p class="mt-1 text-sm text-slate-500" id="adminForecastIntro">Loading forecast summary...</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
      <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Average / Day</div>
        <div class="mt-1 text-xl font-semibold text-slate-900" id="adminForecastAverage">--</div>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Peak Day</div>
        <div class="mt-1 text-xl font-semibold text-slate-900" id="adminForecastPeak">--</div>
      </div>
    </div>
    <div class="mt-4 relative min-h-[160px]">
      <canvas id="adminForecastChart" height="120"></canvas>
    </div>
    <a class="inline-flex items-center mt-4 text-sm font-medium text-slate-600 hover:text-slate-900" href="/HealthLogs/public/forecast.php">
      Open full forecast <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
    </a>
  </div>
</div>

<script>
  let adminForecastChartInstance = null;

  (function () {
    const introEl = document.getElementById('adminForecastIntro');
    const averageEl = document.getElementById('adminForecastAverage');
    const peakEl = document.getElementById('adminForecastPeak');
    const canvas = document.getElementById('adminForecastChart');

    function renderFallback(message) {
      introEl.textContent = message;
      averageEl.textContent = '--';
      peakEl.textContent = '--';
    }

    fetch('/HealthLogs/public/forecast_run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ series_key: 'visits_total', horizon: '14', fast: '1' })
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error || !data.summary || !data.forecast) {
          throw new Error(data.error || 'Forecast unavailable');
        }

        introEl.textContent = data.summary.intro;
        averageEl.textContent = Number(data.summary.forecast_average).toFixed(1);
        peakEl.textContent = data.summary.peak_date;

        adminForecastChartInstance = new Chart(canvas, {
          type: 'line',
          data: {
            labels: data.forecast.map((row) => row.date),
            datasets: [{
              label: 'Visits forecast',
              data: data.forecast.map((row) => row.value),
              borderColor: '#0f172a',
              backgroundColor: 'rgba(15,23,42,0.08)',
              fill: true,
              tension: 0.3,
              pointRadius: 1.5,
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
              x: { ticks: { maxTicksLimit: 6 } },
              y: { beginAtZero: true }
            }
          }
        });
      })
      .catch(() => {
        renderFallback('Forecast is not ready yet. Open the full forecast page to review details.');
      });
  }());

  function printDashboardReport() {
    const printUser = <?= json_encode($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System Administrator') ?>;
    const printRole = 'System Administrator / Admin';
    const printDate = <?= json_encode(date('F j, Y, h:i A')) ?>;
    const todayDate = <?= json_encode(date('M d, Y')) ?>;

    const totalPatients = <?= json_encode(number_format($adminStats['total_patients'])) ?>;
    const monthlyVisits = <?= json_encode(number_format($adminStats['monthly_visits'])) ?>;
    const lowStockItems = <?= json_encode(number_format($adminStats['low_stock_items'])) ?>;
    const activeUsers = <?= json_encode(number_format($adminStats['active_users'])) ?>;
    const pendingReminders = <?= json_encode(number_format($adminStats['pending_reminders'])) ?>;
    const ongoingPregnancies = <?= json_encode(number_format($adminStats['ongoing_pregnancies'])) ?>;

    const avgEl = document.getElementById('adminForecastAverage');
    const peakEl = document.getElementById('adminForecastPeak');
    const forecastAvg = avgEl ? avgEl.textContent : '--';
    const forecastPeak = peakEl ? peakEl.textContent : '--';

    let chartImgHtml = '';
    const chartCanvas = document.getElementById('adminForecastChart');
    if (chartCanvas) {
      try {
        const chartDataUrl = chartCanvas.toDataURL('image/png');
        chartImgHtml = `<div style="margin-top: 14px; text-align: center;"><img src="${chartDataUrl}" style="max-width: 100%; height: auto; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px;" alt="Visits Forecast Chart" /></div>`;
      } catch (e) {}
    }

    const win = window.open('', '_blank', 'width=1100,height=800');
    if (!win) {
      alert('Popup blocker prevented opening the print window. Please allow popups for this site.');
      return;
    }

    win.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Executive Administrative & Program Summary Report</title>
        <link rel="icon" type="image/jpeg" href="/HealthLogs/public/assets/images/logo.jpeg">
        <style>
          @page {
            size: auto;
            margin: 15mm 12mm 15mm 12mm;
          }
          body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 10px;
            font-size: 11.5px;
            line-height: 1.4;
          }
          .official-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
          }
          .header-center {
            text-align: center;
            flex: 1;
            padding: 0 15px;
          }
          .rep-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 600; }
          .agency-title { font-size: 11px; text-transform: uppercase; color: #334155; font-weight: 600; margin-top: 1px; }
          .hub-title { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-top: 2px; }
          .sys-title { font-size: 11px; color: #0f766e; font-weight: 700; margin-top: 1px; }
          .doc-meta-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
          }
          .doc-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }
          .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
            margin: 16px 0 8px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
          }
          .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
          }
          .kpi-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 6px;
            padding: 10px 12px;
          }
          .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 600;
          }
          .kpi-val {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
          }
          .kpi-sub {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 11px;
          }
          th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.5px;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
          }
          td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            color: #1e293b;
          }
          tr:nth-child(even) td {
            background-color: #f8fafc;
          }
          .signatory-grid {
            margin-top: 36px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            page-break-inside: avoid;
            text-align: center;
          }
          .sig-box {
            display: flex;
            flex-direction: column;
          }
          .sig-label {
            font-size: 10.5px;
            color: #475569;
            text-align: left;
            margin-bottom: 38px;
          }
          .sig-name {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            border-bottom: 1px solid #0f172a;
            padding-bottom: 2px;
          }
          .sig-role {
            font-size: 10px;
            color: #475569;
            margin-top: 3px;
          }
          .sig-date {
            font-size: 9.5px;
            color: #94a3b8;
            margin-top: 2px;
          }
          .watermark-footer {
            margin-top: 24px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
          }
        </style>
      </head>
      <body>
        <div class="official-header">
          <div style="display: flex; align-items: center;">
            <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" style="width: 58px; height: 58px; object-fit: cover; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
          </div>
          <div class="header-center">
            <div class="rep-title">Republic of the Philippines</div>
            <div class="agency-title">Department of Health • Primary Care Services</div>
            <div class="hub-title">Barangay Health Center & Care Hub</div>
            <div class="sys-title">HealthLogs Information Management System</div>
          </div>
          <div style="text-align: right; font-size: 10px; color: #64748b;">
            <div><strong>Date:</strong> ${todayDate}</div>
            <div><strong>Time:</strong> <?= date('h:i A') ?></div>
          </div>
        </div>

        <div class="doc-meta-box">
          <div>
            <div class="doc-title">Official Report: Executive Administrative & Program Summary</div>
            <div style="color: #475569; margin-top: 2px;"><strong>Scope:</strong> Primary Healthcare Operations, Active Programs & Operational Metrics</div>
          </div>
          <div style="text-align: right; color: #475569;">
            <div><strong>Generated By:</strong> ${printUser}</div>
            <div><strong>Designation:</strong> ${printRole}</div>
          </div>
        </div>

        <div class="section-title">Key Program Metrics & Indicators</div>
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-label">Registered Patients</div>
            <div class="kpi-val">${totalPatients}</div>
            <div class="kpi-sub">Total active records</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-label">Monthly Clinic Visits</div>
            <div class="kpi-val">${monthlyVisits}</div>
            <div class="kpi-sub">Recorded this month</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-label">Low Stock Medicines</div>
            <div class="kpi-val">${lowStockItems}</div>
            <div class="kpi-sub">Items below reorder point</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-label">Active User Accounts</div>
            <div class="kpi-val">${activeUsers}</div>
            <div class="kpi-sub">Staff & administrative accounts</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-label">Pending Reminders</div>
            <div class="kpi-val">${pendingReminders}</div>
            <div class="kpi-sub">Due for SMS / follow-up</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-label">Ongoing Pregnancies</div>
            <div class="kpi-val">${ongoingPregnancies}</div>
            <div class="kpi-sub">Active maternal cases</div>
          </div>
        </div>

        <div class="section-title">Core Program Status</div>
        <table>
          <thead>
            <tr>
              <th>Program / Domain</th>
              <th>Status</th>
              <th>Operational Summary</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Immunization Module</strong></td>
              <td><span style="color:#047857; font-weight:700;">Active & On Track</span></td>
              <td>Routine vaccines and scheduled outreach tracking for infants and children.</td>
            </tr>
            <tr>
              <td><strong>Maternal Health</strong></td>
              <td><span style="color:#047857; font-weight:700;">Monitored</span></td>
              <td>Prenatal vital checks, gestational age tracking, and postpartum care.</td>
            </tr>
            <tr>
              <td><strong>Medicine Inventory</strong></td>
              <td><span style="color:${lowStockItems > '0' ? '#b91c1c' : '#047857'}; font-weight:700;">${lowStockItems > '0' ? 'Needs Attention' : 'Optimal'}</span></td>
              <td>Stock replenishment monitoring with ${lowStockItems} items below reorder threshold.</td>
            </tr>
            <tr>
              <td><strong>Patient Engagement & Reminders</strong></td>
              <td><span style="color:#047857; font-weight:700;">Automated</span></td>
              <td>Daily scheduled SMS reminders dispatched to patients for upcoming care visits.</td>
            </tr>
          </tbody>
        </table>

        <div class="section-title">Visit Forecast Snapshot & Resource Planning</div>
        <table>
          <thead>
            <tr>
              <th>Forecast Metric</th>
              <th>Projected Value</th>
              <th>Clinical Planning Insight</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Projected Average Intake</strong></td>
              <td><strong>${forecastAvg} visits / day</strong></td>
              <td>Estimated daily patient visit load over the next forecasting horizon.</td>
            </tr>
            <tr>
              <td><strong>Expected Peak Date</strong></td>
              <td><strong>${forecastPeak}</strong></td>
              <td>Projected high-volume intake day requiring adequate staff and supply allocation.</td>
            </tr>
          </tbody>
        </table>

        ${chartImgHtml}

        <div class="signatory-grid">
          <div class="sig-box">
            <div class="sig-label">Prepared by:</div>
            <div class="sig-name">${printUser}</div>
            <div class="sig-role">${printRole}</div>
            <div class="sig-date">Date: ${todayDate}</div>
          </div>
          <div class="sig-box">
            <div class="sig-label">Verified by:</div>
            <div class="sig-name">___________________________</div>
            <div class="sig-role">Supervising Public Health Nurse</div>
            <div class="sig-date">Date: ____________________</div>
          </div>
          <div class="sig-box">
            <div class="sig-label">Approved by:</div>
            <div class="sig-name">___________________________</div>
            <div class="sig-role">Municipal Health Officer / Physician</div>
            <div class="sig-date">Date: ____________________</div>
          </div>
        </div>

        <div class="watermark-footer">
          Official HealthLogs System Generated Document • Certified Executive Summary • Timestamp: ${printDate}
        </div>
      </body>
      </html>
    `);

    win.document.close();
    win.focus();
    setTimeout(() => {
      win.print();
    }, 400);
  }
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
