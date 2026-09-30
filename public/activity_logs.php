<?php
require __DIR__ . '/partials/bootstrap.php';

// RBAC: Only Admin can access Activity Logs
if (!is_admin()) {
    header('Location: /HealthLogs/public/index.php');
    exit;
}

$pageTitle = 'Activity Logs & System Audit';

// Parameters
$q = trim($_GET['q'] ?? '');
$moduleFilter = trim($_GET['module'] ?? '');
$actionFilter = trim($_GET['action'] ?? '');
$userFilter = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$datePreset = trim($_GET['preset'] ?? '');

// Handle presets
if ($datePreset === 'today') {
    $dateFrom = date('Y-m-d');
    $dateTo = date('Y-m-d');
} elseif ($datePreset === '7days') {
    $dateFrom = date('Y-m-d', strtotime('-7 days'));
    $dateTo = date('Y-m-d');
} elseif ($datePreset === '30days') {
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo = date('Y-m-d');
} elseif ($datePreset === 'month') {
    $dateFrom = date('Y-m-01');
    $dateTo = date('Y-m-t');
}

// Build query
$whereClauses = [];
$params = [];

if ($q !== '') {
    $whereClauses[] = "(a.description LIKE ? OR a.username LIKE ? OR a.ip_address LIKE ? OR COALESCE(a.entity_id, '') LIKE ? OR a.details LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($moduleFilter !== '') {
    $whereClauses[] = "a.module = ?";
    $params[] = $moduleFilter;
}

if ($actionFilter !== '') {
    $whereClauses[] = "a.action = ?";
    $params[] = $actionFilter;
}

if ($userFilter > 0) {
    $whereClauses[] = "a.user_id = ?";
    $params[] = $userFilter;
}

if ($dateFrom !== '') {
    $whereClauses[] = "DATE(a.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $whereClauses[] = "DATE(a.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportSql = "SELECT a.id, a.created_at, a.username, a.user_role, a.module, a.action, a.description, a.entity_type, a.entity_id, a.ip_address, a.details
                  FROM activity_logs a
                  $whereSql
                  ORDER BY a.created_at DESC, a.id DESC";
    $exportStmt = $pdo->prepare($exportSql);
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Log ID', 'Timestamp', 'Username', 'Role', 'Module', 'Action', 'Description', 'Entity Type', 'Entity ID', 'IP Address', 'Details (JSON)']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['created_at'],
            $r['username'] ?: 'System',
            $r['user_role'] ?: 'N/A',
            ucfirst($r['module']),
            $r['action'],
            $r['description'],
            $r['entity_type'] ?: 'N/A',
            $r['entity_id'] ?: 'N/A',
            $r['ip_address'] ?: 'N/A',
            $r['details'] ?: ''
        ]);
    }
    fclose($out);
    exit;
}

// KPI Stats
$stats = [
    'total' => 0,
    'auth' => 0,
    'inventory' => 0,
    'clinical' => 0
];

try {
    $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
    $stats['auth'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'auth'")->fetchColumn();
    $stats['inventory'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory'")->fetchColumn();
    $stats['clinical'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module IN ('patients', 'immunization', 'maternal', 'family_planning', 'ncd')")->fetchColumn();
} catch (Throwable $e) {}

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs a $whereSql");
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();

$paginator = paginate($totalFiltered, 20);

// Fetch page logs
$querySql = "SELECT a.*, u.full_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             $whereSql
             ORDER BY a.created_at DESC, a.id DESC
             " . $paginator->getLimitSql();
$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch users for dropdown
$usersList = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Distinct modules & actions for filters
$modulesList = [
    'inventory' => 'Medicine Inventory',
    'auth' => 'Authentication & Security',
    'patients' => 'Patients Management',
    'users' => 'User Management',
    'immunization' => 'Immunization Program',
    'maternal' => 'Maternal Health',
    'family_planning' => 'Family Planning',
    'ncd' => 'Non-Communicable Diseases',
    'reminders' => 'Reminders & Notifications',
    'forecast' => 'Forecasting Models'
];

$actionsList = [
    'login' => 'User Login',
    'logout' => 'User Logout',
    'login_failed' => 'Failed Login Attempt',
    'medicine_add' => 'Medicine Registration',
    'medicine_update' => 'Medicine Profile Update',
    'medicine_delete' => 'Medicine Deletion',
    'stock_receive' => 'Stock Received / Batch Added',
    'stock_update' => 'Stock / Batch Update',
    'issuance' => 'Stock Released / Issued',
    'return' => 'Stock Returned',
    'adjustment' => 'Inventory Adjustment',
    'create' => 'Record Created',
    'update' => 'Record Updated',
    'delete' => 'Record Deleted'
];

require __DIR__ . '/partials/header.php';
?>

<!-- Header Banner -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-teal-600 uppercase tracking-widest">
        <i class="fas fa-shield-halved"></i>
        <span>Security, Accountability & Audit Trail</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mt-1">Activity Logs</h1>
      <p class="text-sm text-slate-500 mt-1">Comprehensive system audit trail tracking user interactions, medicine inventory transactions, and data changes.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/inventory/audit_trail.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 text-xs font-semibold transition">
        <i class="fas fa-boxes-stacked"></i>
        <span>Medicine Audit Trail</span>
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-xs font-semibold transition shadow-xs">
        <i class="fas fa-file-csv"></i>
        <span>Export CSV</span>
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 text-xs font-semibold transition">
        <i class="fas fa-print"></i>
        <span>Print</span>
      </button>
    </div>
  </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-list-check"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Total Activities</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['total']) ?></div>
      <div class="text-xs text-slate-400 truncate">Recorded system events</div>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-user-shield"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Auth & Security</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['auth']) ?></div>
      <div class="text-xs text-slate-400 truncate">Logins & authentication</div>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-pills"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Inventory Audits</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['inventory']) ?></div>
      <div class="text-xs text-slate-400 truncate">Additions, issuances, returns</div>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-hospital-user"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Clinical Logs</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['clinical']) ?></div>
      <div class="text-xs text-slate-400 truncate">Patient & program records</div>
    </div>
  </div>
</div>

<!-- Filter Box -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
  <form method="get" action="/HealthLogs/public/activity_logs.php" class="space-y-4">
    <!-- Search and Main Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Search Keyword -->
      <div class="lg:col-span-2">
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Search Keywords</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
            <i class="fas fa-search text-xs"></i>
          </span>
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search description, username, reference, IP address..." class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
        </div>
      </div>

      <!-- Module Filter -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Module / Area</label>
        <select name="module" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
          <option value="">All Modules</option>
          <?php foreach ($modulesList as $modKey => $modLabel): ?>
            <option value="<?= h($modKey) ?>" <?= $moduleFilter === $modKey ? 'selected' : '' ?>><?= h($modLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action Filter -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Action Type</label>
        <select name="action" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
          <option value="">All Actions</option>
          <?php foreach ($actionsList as $actKey => $actLabel): ?>
            <option value="<?= h($actKey) ?>" <?= $actionFilter === $actKey ? 'selected' : '' ?>><?= h($actLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Date Range & User Filter -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
      <!-- User Filter -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Performed By (User)</label>
        <select name="user_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
          <option value="">All Users</option>
          <?php foreach ($usersList as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $userFilter === (int)$u['id'] ? 'selected' : '' ?>>
              <?= h($u['username']) ?><?= $u['full_name'] ? ' (' . h($u['full_name']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date From -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Date From</label>
        <input type="date" name="date_from" value="<?= h($dateFrom) ?>" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Date To -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Date To</label>
        <input type="date" name="date_to" value="<?= h($dateTo) ?>" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Action Buttons & Quick Presets -->
      <div class="flex items-end gap-2">
        <button type="submit" class="flex-1 bg-slate-900 text-white hover:bg-slate-800 px-4 py-2 rounded-xl text-sm font-semibold transition shadow-xs">
          <i class="fas fa-filter mr-1.5 text-xs"></i> Filter
        </button>
        <a href="/HealthLogs/public/activity_logs.php" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition">
          Clear
        </a>
      </div>
    </div>

    <!-- Quick Date Presets -->
    <div class="flex flex-wrap items-center gap-2 pt-2 text-xs">
      <span class="text-slate-400 font-medium">Quick Presets:</span>
      <a href="?preset=today" class="px-2.5 py-1 rounded-lg border <?= $datePreset === 'today' ? 'bg-teal-600 text-white border-teal-600 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">Today</a>
      <a href="?preset=7days" class="px-2.5 py-1 rounded-lg border <?= $datePreset === '7days' ? 'bg-teal-600 text-white border-teal-600 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">Last 7 Days</a>
      <a href="?preset=30days" class="px-2.5 py-1 rounded-lg border <?= $datePreset === '30days' ? 'bg-teal-600 text-white border-teal-600 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">Last 30 Days</a>
      <a href="?preset=month" class="px-2.5 py-1 rounded-lg border <?= $datePreset === 'month' ? 'bg-teal-600 text-white border-teal-600 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">This Month</a>
      <a href="/HealthLogs/public/activity_logs.php" class="px-2.5 py-1 rounded-lg border <?= empty($datePreset) && empty($dateFrom) && empty($dateTo) ? 'bg-slate-900 text-white border-slate-900 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">All Time</a>
    </div>
  </form>
</div>

<!-- Table Card -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-6">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="text-sm font-bold text-slate-900">Audit Trail Records</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600"><?= number_format($totalFiltered) ?> matches</span>
    </div>
    <div class="text-xs text-slate-400">Page <?= $paginator->getCurrentPage() ?> of <?= max(1, $paginator->getTotalPages()) ?></div>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
          <th class="py-3.5 px-4">Date & Time</th>
          <th class="py-3.5 px-4">User</th>
          <th class="py-3.5 px-4">Module</th>
          <th class="py-3.5 px-4">Action</th>
          <th class="py-3.5 px-4">Description</th>
          <th class="py-3.5 px-4">IP & Source</th>
          <th class="py-3.5 px-4 text-right">Details</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm">
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="7" class="py-12 text-center text-slate-500">
              <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                <i class="fas fa-clock-rotate-left"></i>
              </div>
              <p class="font-medium text-slate-700">No activity logs found</p>
              <p class="text-xs text-slate-400 mt-1">Try broadening your search or resetting the active filters.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <?php
              $modColor = 'bg-slate-100 text-slate-700 border-slate-200';
              $modIcon = 'fa-cube';
              if ($log['module'] === 'inventory') {
                  $modColor = 'bg-amber-50 text-amber-700 border-amber-200';
                  $modIcon = 'fa-pills';
              } elseif ($log['module'] === 'auth') {
                  $modColor = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                  $modIcon = 'fa-shield-halved';
              } elseif ($log['module'] === 'patients') {
                  $modColor = 'fa-hospital-user bg-teal-50 text-teal-700 border-teal-200';
                  $modIcon = 'fa-user';
              } elseif ($log['module'] === 'users') {
                  $modColor = 'bg-purple-50 text-purple-700 border-purple-200';
                  $modIcon = 'fa-user-gear';
              } elseif (in_array($log['module'], ['immunization', 'maternal', 'family_planning', 'ncd'])) {
                  $modColor = 'bg-sky-50 text-sky-700 border-sky-200';
                  $modIcon = 'fa-notes-medical';
              }

              $actBadge = 'bg-slate-100 text-slate-700';
              if (in_array($log['action'], ['create', 'medicine_add', 'batch_add', 'stock_receive', 'login'])) {
                  $actBadge = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
              } elseif (in_array($log['action'], ['update', 'medicine_update', 'batch_update', 'stock_update', 'status_change'])) {
                  $actBadge = 'bg-amber-50 text-amber-700 border border-amber-200';
              } elseif (in_array($log['action'], ['issuance'])) {
                  $actBadge = 'bg-blue-50 text-blue-700 border border-blue-200';
              } elseif (in_array($log['action'], ['return'])) {
                  $actBadge = 'bg-teal-50 text-teal-700 border border-teal-200';
              } elseif (in_array($log['action'], ['adjustment'])) {
                  $actBadge = 'bg-purple-50 text-purple-700 border border-purple-200';
              } elseif (in_array($log['action'], ['delete', 'medicine_delete', 'batch_delete', 'transaction_delete', 'login_failed', 'logout'])) {
                  $actBadge = 'bg-rose-50 text-rose-700 border border-rose-200';
              }

              $detailsData = !empty($log['details']) ? json_decode($log['details'], true) : null;
            ?>
            <tr class="hover:bg-slate-50/60 transition group">
              <!-- Timestamp -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="text-xs font-semibold text-slate-800"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                <div class="text-[11px] text-slate-400"><?= date('h:i:s A', strtotime($log['created_at'])) ?></div>
              </td>

              <!-- User -->
              <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-600 shrink-0 uppercase">
                    <?= substr($log['username'] ?: 'S', 0, 1) ?>
                  </div>
                  <div class="min-w-0">
                    <div class="text-xs font-semibold text-slate-900 truncate">
                      <?= h($log['full_name'] ?: ($log['username'] ?: 'System')) ?>
                    </div>
                    <div class="text-[11px] text-slate-400 truncate">
                      <?= h(ucfirst($log['user_role'] ?: 'System')) ?>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Module -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border <?= $modColor ?>">
                  <i class="fas <?= $modIcon ?> text-[10px]"></i>
                  <span><?= h(ucfirst(str_replace('_', ' ', $log['module']))) ?></span>
                </span>
              </td>

              <!-- Action -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider <?= $actBadge ?>">
                  <?= h(str_replace('_', ' ', $log['action'])) ?>
                </span>
              </td>

              <!-- Description -->
              <td class="py-3 px-4">
                <div class="text-xs text-slate-800 font-medium leading-relaxed">
                  <?= h($log['description']) ?>
                </div>
                <?php if (!empty($log['entity_type'])): ?>
                  <div class="text-[11px] text-slate-400 mt-0.5">
                    Entity: <span class="font-mono text-slate-500"><?= h($log['entity_type']) ?></span>
                    <?php if (!empty($log['entity_id'])): ?>
                      <span class="ml-1 font-mono text-slate-500">#<?= h($log['entity_id']) ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- IP Address -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="text-xs font-mono text-slate-600"><?= h($log['ip_address'] ?: '127.0.0.1') ?></div>
                <div class="text-[10px] text-slate-400 truncate max-w-[130px]" title="<?= h($log['user_agent'] ?? '') ?>">
                  <?= h(substr($log['user_agent'] ?: 'Web Client', 0, 24)) ?>...
                </div>
              </td>

              <!-- Details Modal Trigger -->
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <button type="button" class="view-log-btn inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition"
                  data-log-id="<?= (int)$log['id'] ?>"
                  data-timestamp="<?= h($log['created_at']) ?>"
                  data-user="<?= h($log['full_name'] ?: ($log['username'] ?: 'System')) ?>"
                  data-role="<?= h($log['user_role'] ?: 'System') ?>"
                  data-module="<?= h($log['module']) ?>"
                  data-action="<?= h($log['action']) ?>"
                  data-desc="<?= h($log['description']) ?>"
                  data-ip="<?= h($log['ip_address'] ?: '127.0.0.1') ?>"
                  data-agent="<?= h($log['user_agent'] ?? '') ?>"
                  data-details='<?= htmlspecialchars(json_encode($detailsData ?: []), ENT_QUOTES, 'UTF-8') ?>'>
                  <i class="fas fa-eye text-teal-600 text-xs"></i>
                  <span>Inspect</span>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<div class="mb-8">
  <?= $paginator->render() ?>
</div>

<!-- Log Inspection Modal -->
<div id="logDetailModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" id="logModalBackdrop"></div>
  <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold">
            <i class="fas fa-file-invoice"></i>
          </div>
          <div>
            <div class="text-sm font-bold text-slate-900" id="modalLogTitle">Audit Log Record #<span id="modalLogId"></span></div>
            <div class="text-xs text-slate-400" id="modalLogTime"></div>
          </div>
        </div>
        <button type="button" id="modalCloseBtn" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 flex items-center justify-center transition">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-4">
        <!-- Overview Grid -->
        <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs">
          <div>
            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Actor / User:</span>
            <span class="font-medium text-slate-800" id="modalUser"></span>
            <span class="text-slate-500 block text-[11px]" id="modalRole"></span>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Module & Action:</span>
            <span class="font-medium text-slate-800" id="modalModuleAction"></span>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-semibold block text-[10px]">IP Address:</span>
            <span class="font-mono text-slate-800" id="modalIp"></span>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Client Agent:</span>
            <span class="text-slate-700 truncate block" id="modalAgent"></span>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Description</label>
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900" id="modalDesc"></div>
        </div>

        <!-- Structured Details Table -->
        <div id="modalStructuredDetailsContainer" class="hidden">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Structured Audit Payload</label>
          <div class="border border-slate-200 rounded-xl overflow-hidden">
            <table class="min-w-full text-xs">
              <tbody id="modalStructuredTable" class="divide-y divide-slate-100"></tbody>
            </table>
          </div>
        </div>

        <!-- Raw JSON Payload View -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Raw Details (JSON)</label>
            <button type="button" id="copyJsonBtn" class="text-xs text-teal-600 hover:text-teal-700 font-medium">
              <i class="fas fa-copy mr-1"></i> Copy JSON
            </button>
          </div>
          <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl text-xs font-mono overflow-x-auto max-h-56 leading-relaxed" id="modalRawJson"></pre>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
        <button type="button" id="modalCloseFooterBtn" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
          Close Inspector
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const modal = document.getElementById('logDetailModal');
  const backdrop = document.getElementById('logModalBackdrop');
  const closeBtn = document.getElementById('modalCloseBtn');
  const closeFooterBtn = document.getElementById('modalCloseFooterBtn');

  function closeModal() {
    modal.classList.add('hidden');
  }

  function openModal(data) {
    document.getElementById('modalLogId').textContent = data.logId;
    document.getElementById('modalLogTime').textContent = data.timestamp;
    document.getElementById('modalUser').textContent = data.user;
    document.getElementById('modalRole').textContent = '(' + data.role + ')';
    document.getElementById('modalModuleAction').textContent = data.module.toUpperCase() + ' • ' + data.action;
    document.getElementById('modalIp').textContent = data.ip;
    document.getElementById('modalAgent').textContent = data.agent || 'Not captured';
    document.getElementById('modalDesc').textContent = data.desc;

    let parsed = {};
    try {
      parsed = JSON.parse(data.details);
    } catch(e) {
      parsed = data.details || {};
    }

    const structuredContainer = document.getElementById('modalStructuredDetailsContainer');
    const structuredTable = document.getElementById('modalStructuredTable');
    structuredTable.innerHTML = '';

    if (parsed && typeof parsed === 'object' && Object.keys(parsed).length > 0) {
      structuredContainer.classList.remove('hidden');
      for (const [key, val] of Object.entries(parsed)) {
        if (typeof val === 'object' && val !== null) {
          continue;
        }
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50';
        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        tr.innerHTML = `
          <td class="py-2 px-3 font-semibold text-slate-500 w-1/3 bg-slate-50/60">${formattedKey}</td>
          <td class="py-2 px-3 text-slate-900 font-mono text-[11px]">${val !== null ? String(val) : '<span class="text-slate-400">null</span>'}</td>
        `;
        structuredTable.appendChild(tr);
      }
    } else {
      structuredContainer.classList.add('hidden');
    }

    const jsonStr = JSON.stringify(parsed, null, 2);
    document.getElementById('modalRawJson').textContent = jsonStr;

    modal.classList.remove('hidden');
  }

  document.querySelectorAll('.view-log-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      openModal({
        logId: this.dataset.logId,
        timestamp: this.dataset.timestamp,
        user: this.dataset.user,
        role: this.dataset.role,
        module: this.dataset.module,
        action: this.dataset.action,
        desc: this.dataset.desc,
        ip: this.dataset.ip,
        agent: this.dataset.agent,
        details: this.dataset.details
      });
    });
  });

  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (closeFooterBtn) closeFooterBtn.addEventListener('click', closeModal);

  // Copy JSON button
  const copyBtn = document.getElementById('copyJsonBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function() {
      const code = document.getElementById('modalRawJson').textContent;
      navigator.clipboard.writeText(code).then(() => {
        copyBtn.innerHTML = '<i class="fas fa-check mr-1 text-emerald-600"></i> Copied!';
        setTimeout(() => {
          copyBtn.innerHTML = '<i class="fas fa-copy mr-1"></i> Copy JSON';
        }, 2000);
      });
    });
  }
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
