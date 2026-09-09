<?php
$pageTitle = 'Administrator Dashboard';
require __DIR__ . '/../partials/header.php';

$adminStats = [
    'total_patients' => 0,
    'monthly_visits' => 0,
    'low_stock_items' => 0,
    'active_users' => 0,
    'pending_reminders' => 0,
    'ongoing_pregnancies' => 0,
];

try {
    $adminStats['total_patients'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM patients WHERE status = 'active'"
    )->fetchColumn();

    $adminStats['monthly_visits'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM visits
         WHERE MONTH(visit_datetime) = MONTH(CURDATE())
           AND YEAR(visit_datetime) = YEAR(CURDATE())"
    )->fetchColumn();

    $adminStats['low_stock_items'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM (
             SELECT m.id
             FROM medicines m
             LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id
             GROUP BY m.id, m.reorder_level
             HAVING COALESCE(SUM(mt.quantity), 0) <= COALESCE(m.reorder_level, 0)
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
} catch (Throwable $e) {
    // Keep dashboard usable even if a summary query fails.
}
?>

<div class="bg-white/90 backdrop-blur-md p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-semibold uppercase tracking-wider text-teal-700 flex items-center gap-1.5 mb-1">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Primary Care Command Center
      </div>
      <div class="text-2xl sm:text-3xl font-bold text-slate-900 brand-font">Administrator Dashboard</div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        Welcome back, <strong class="text-slate-800"><?= h($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong>. System overview, patient analytics, and operational controls.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-semibold">
        <i class="fas fa-shield-check text-teal-600"></i> Admin Access
      </span>
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold">
        <i class="fas fa-bolt text-blue-600"></i> Full System Scope
      </span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
  <!-- Total Patients -->
  <div class="bg-white/95 backdrop-blur-xs p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Patients</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= h(number_format($adminStats['total_patients'])) ?></div>
      <div class="text-xs text-slate-500 mt-0.5">Active registered records</div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-100 text-sky-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-users-medical"></i>
    </div>
  </div>

  <!-- Monthly Visits -->
  <div class="bg-white/95 backdrop-blur-xs p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Monthly Visits</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= h(number_format($adminStats['monthly_visits'])) ?></div>
      <div class="text-xs text-emerald-600 font-medium mt-0.5"><i class="fas fa-calendar-check mr-1"></i>Recorded this month</div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-notes-medical"></i>
    </div>
  </div>

  <!-- Low Stock Alert -->
  <div class="bg-white/95 backdrop-blur-xs p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Supplies</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= h(number_format($adminStats['low_stock_items'])) ?></div>
      <div class="text-xs <?= $adminStats['low_stock_items'] > 0 ? 'text-amber-600 font-medium' : 'text-slate-500' ?> mt-0.5">
        <?= $adminStats['low_stock_items'] > 0 ? '<i class="fas fa-triangle-exclamation mr-1"></i>Needs restock' : 'Supplies adequate' ?>
      </div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-boxes-stacked"></i>
    </div>
  </div>

  <!-- Active Accounts -->
  <div class="bg-white/95 backdrop-blur-xs p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Staff Accounts</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= h(number_format($adminStats['active_users'])) ?></div>
      <div class="text-xs text-slate-500 mt-0.5">Authorized clinicians & BHWs</div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-user-shield"></i>
    </div>
  </div>

  <!-- Pending Reminders -->
  <div class="bg-white/95 backdrop-blur-xs p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Due Reminders</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= h(number_format($adminStats['pending_reminders'])) ?></div>
      <div class="text-xs <?= $adminStats['pending_reminders'] > 0 ? 'text-rose-600 font-medium' : 'text-slate-500' ?> mt-0.5">
        <?= $adminStats['pending_reminders'] > 0 ? '<i class="fas fa-clock mr-1"></i>Due for dispatch' : 'All dispatched' ?>
      </div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-bell"></i>
    </div>
  </div>

  <!-- Ongoing Pregnancies -->
  <div class="bg-white/95 backdrop-blur-xs p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Maternal Cases</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= h(number_format($adminStats['ongoing_pregnancies'])) ?></div>
      <div class="text-xs text-pink-600 font-medium mt-0.5"><i class="fas fa-heart-pulse mr-1"></i>Active prenatal monitoring</div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-pink-50 border border-pink-100 text-pink-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-baby"></i>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow xl:col-span-2">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Program Performance</div>
        <div class="text-lg font-semibold">Visits & Outreach</div>
      </div>
      <a class="text-blue-700 text-sm" href="/HealthLogs/public/forecast.php">View Forecast</a>
    </div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Immunization</div>
        <div class="text-xl font-semibold mt-1">On Track</div>
        <p class="text-slate-500 text-sm mt-1">Coverage above last quarter.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Maternal Health</div>
        <div class="text-xl font-semibold mt-1">Stable</div>
        <p class="text-slate-500 text-sm mt-1">Prenatal visits consistent.</p>
      </div>

      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Medicine Demand</div>
        <div class="text-xl font-semibold mt-1">Rising</div>
        <p class="text-slate-500 text-sm mt-1">Expect higher demand next month.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Roles</div>
        <div class="text-xl font-semibold mt-1">2 Active</div>
        <p class="text-slate-500 text-sm mt-1">Admin and Health Worker access levels.</p>
      </div>
    </div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Quick Actions</div>
    <div class="text-lg font-semibold">Administrator Tools</div>
    <div class="mt-4 space-y-2">
      <a class="block px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 transition-colors" href="/HealthLogs/public/users.php">
        <i class="fas fa-users mr-2"></i>Manage Users
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/inventory.php">
        <i class="fas fa-pills mr-2"></i>Review Inventory
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/reminders.php">
        <i class="fas fa-bell mr-2"></i>Review Reminders
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/forecast.php">
        <i class="fas fa-chart-line mr-2"></i>Run Forecast
      </a>
    </div>
  </div>
</div>

<div class="bg-white p-4 sm:p-5 rounded-xl shadow mt-6">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <div class="text-sm text-slate-500">Reports</div>
      <div class="text-lg font-semibold text-slate-900">Administrative Summary</div>
      <p class="text-sm text-slate-500 mt-1">Official printable overview of primary healthcare operations, program metrics, and clinical trends.</p>
    </div>
    <button class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow hover:bg-slate-800 transition" onclick="printDashboardReport()">
      <i class="fas fa-print mr-2"></i>Print Report
    </button>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Coverage</div>
      <div class="text-xl font-semibold mt-1 text-slate-900">Immunization</div>
      <p class="text-slate-500 text-sm mt-1">Monitor completion rates and missed schedules.</p>
    </div>
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Care</div>
      <div class="text-xl font-semibold mt-1 text-slate-900">Maternal Health</div>
      <p class="text-slate-500 text-sm mt-1">Track prenatal and postnatal visit consistency.</p>
    </div>
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Inventory</div>
      <div class="text-xl font-semibold mt-1 text-slate-900">Stock Health</div>
      <p class="text-slate-500 text-sm mt-1">Identify low-stock and expiring medicines.</p>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Outreach</div>
      <div class="text-xl font-semibold mt-1 text-slate-900">Reminders</div>
      <p class="text-slate-500 text-sm mt-1">Upcoming tasks and overdue follow-ups.</p>
    </div>
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Forecast</div>
      <div class="text-xl font-semibold mt-1 text-slate-900">Next 30 Days</div>
      <p class="text-slate-500 text-sm mt-1">Projected demand for planning resources.</p>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-4 sm:p-5 rounded-xl shadow">
    <div class="text-sm text-slate-500">Alerts</div>
    <div class="text-lg font-semibold text-slate-900">Needs Attention</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-2">
      <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span><?= h((string)$adminStats['low_stock_items']) ?> medicine items below reorder level.</li>
      <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><?= h((string)$adminStats['pending_reminders']) ?> pending reminders due for dispatch.</li>
      <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span><?= h((string)$adminStats['ongoing_pregnancies']) ?> active maternal health ongoing pregnancies.</li>
    </ul>
  </div>
  <div class="bg-white p-4 sm:p-5 rounded-xl shadow">
    <div class="text-sm text-slate-500">Forecast Snapshot</div>
    <div class="text-lg font-semibold text-slate-900">Patient Visits</div>
    <p class="mt-1 text-sm text-slate-500" id="adminForecastIntro">Loading forecast summary...</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Average / Day</div>
        <div class="mt-1 text-xl font-semibold text-slate-900" id="adminForecastAverage">--</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Peak Day</div>
        <div class="mt-1 text-xl font-semibold text-slate-900" id="adminForecastPeak">--</div>
      </div>
    </div>
    <div class="mt-4 relative min-h-[160px]">
      <canvas id="adminForecastChart" height="120"></canvas>
    </div>
    <a class="inline-flex items-center mt-4 text-sm font-medium text-blue-700 hover:text-blue-900" href="/HealthLogs/public/forecast.php">
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
          <div>
            <svg width="60" height="60" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="50" cy="50" r="46" fill="#0f766e" stroke="#115e59" stroke-width="2"/>
              <circle cx="50" cy="50" r="41" fill="#ffffff" stroke="#0f766e" stroke-width="1.5" stroke-dasharray="3 2"/>
              <path d="M43 25 h14 v18 h18 v14 h-18 v18 h-14 v-18 h-18 v-14 h18 z" fill="#0ea5a4" opacity="0.3"/>
              <rect x="44" y="24" width="12" height="52" rx="2" fill="#0f766e"/>
              <rect x="24" y="44" width="52" height="12" rx="2" fill="#0f766e"/>
              <circle cx="50" cy="50" r="7" fill="#ffffff"/>
              <path d="M50 45 L52 49 L56 50 L52 52 L50 56 L48 52 L44 50 L48 49 Z" fill="#0f766e"/>
            </svg>
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
