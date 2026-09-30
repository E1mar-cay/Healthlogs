<?php
require __DIR__ . '/../../public/partials/bootstrap.php';

$pageTitle = 'Medicine Inventory Audit Trail';

// Parameters
$q = trim($_GET['q'] ?? '');
$eventType = trim($_GET['type'] ?? '');
$medId = !empty($_GET['medicine_id']) ? (int)$_GET['medicine_id'] : 0;
$userFilter = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Base where clause for inventory module
$whereClauses = ["a.module = 'inventory'"];
$params = [];

if ($q !== '') {
    $whereClauses[] = "(a.description LIKE ? OR a.username LIKE ? OR COALESCE(a.entity_id, '') LIKE ? OR a.details LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($eventType !== '') {
    if ($eventType === 'addition') {
        $whereClauses[] = "a.action IN ('medicine_add', 'batch_add', 'stock_receive')";
    } elseif ($eventType === 'update') {
        $whereClauses[] = "a.action IN ('stock_update', 'batch_update', 'medicine_update')";
    } elseif ($eventType === 'issuance') {
        $whereClauses[] = "a.action = 'issuance'";
    } elseif ($eventType === 'return') {
        $whereClauses[] = "a.action = 'return'";
    } elseif ($eventType === 'adjustment') {
        $whereClauses[] = "a.action = 'adjustment'";
    } elseif ($eventType === 'deletion') {
        $whereClauses[] = "a.action IN ('medicine_delete', 'batch_delete', 'transaction_delete')";
    } else {
        $whereClauses[] = "a.action = ?";
        $params[] = $eventType;
    }
}

if ($medId > 0) {
    $whereClauses[] = "(JSON_EXTRACT(a.details, '$.medicine_id') = ? OR (a.entity_type = 'medicines' AND a.entity_id = ?))";
    $params[] = $medId;
    $params[] = (string)$medId;
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

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportSql = "SELECT a.id, a.created_at, a.username, a.user_role, a.action, a.description, a.details
                  FROM activity_logs a
                  $whereSql
                  ORDER BY a.created_at DESC, a.id DESC";
    $exportStmt = $pdo->prepare($exportSql);
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="medicine_audit_trail_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Audit ID', 'Timestamp', 'Performed By', 'Role', 'Event Type', 'Description', 'Medicine Name', 'Batch No', 'Qty Change', 'Old Stock', 'New Stock', 'Reference', 'Notes']);
    foreach ($rows as $r) {
        $d = json_decode($r['details'] ?? '[]', true) ?: [];
        fputcsv($out, [
            $r['id'],
            $r['created_at'],
            $r['username'] ?: 'System',
            $r['user_role'] ?: 'N/A',
            $r['action'],
            $r['description'],
            $d['medicine_name'] ?? 'N/A',
            $d['batch_no'] ?? 'N/A',
            isset($d['quantity']) ? ($d['quantity'] >= 0 ? "+{$d['quantity']}" : $d['quantity']) : 'N/A',
            $d['old_stock'] ?? 'N/A',
            $d['new_stock'] ?? 'N/A',
            $d['reference'] ?? 'N/A',
            $d['notes'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// KPI Stats
$stats = [
    'total' => 0,
    'additions' => 0,
    'issuances' => 0,
    'adjustments' => 0,
    'returns' => 0
];

try {
    $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory'")->fetchColumn();
    $stats['additions'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory' AND action IN ('medicine_add', 'batch_add', 'stock_receive')")->fetchColumn();
    $stats['issuances'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory' AND action = 'issuance'")->fetchColumn();
    $stats['adjustments'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory' AND action = 'adjustment'")->fetchColumn();
    $stats['returns'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module = 'inventory' AND action = 'return'")->fetchColumn();
} catch (Throwable $e) {}

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs a $whereSql");
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();

$paginator = paginate($totalFiltered, 20);

// Fetch logs
$querySql = "SELECT a.*, u.full_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             $whereSql
             ORDER BY a.created_at DESC, a.id DESC
             " . $paginator->getLimitSql();
$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Medicines dropdown
$medicinesList = $pdo->query("SELECT id, name FROM medicines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Users dropdown
$usersList = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../../public/partials/header.php';
?>

<!-- Header Banner -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-teal-600 uppercase tracking-widest">
        <i class="fas fa-boxes-stacked"></i>
        <span>Medicine Inventory Management</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mt-1">Medicine Inventory Audit Trail</h1>
      <p class="text-sm text-slate-500 mt-1">Complete verifiable ledger of medicine additions, stock updates, releases/issuances, returns, and inventory adjustments.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/inventory.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-arrow-left"></i>
        <span>Inventory Overview</span>
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

  <!-- Sub-navigation Tabs -->
  <div class="flex flex-wrap gap-2 mt-5 pt-4 border-t border-slate-100 text-xs font-semibold">
    <a href="?" class="px-3 py-1.5 rounded-xl border <?= empty($eventType) ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' ?>">
      All Events (<?= number_format($stats['total']) ?>)
    </a>
    <a href="?type=addition" class="px-3 py-1.5 rounded-xl border <?= $eventType === 'addition' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50/60 text-emerald-800 border-emerald-200 hover:bg-emerald-100' ?>">
      <i class="fas fa-plus-circle mr-1"></i> Additions & Receipts (<?= number_format($stats['additions']) ?>)
    </a>
    <a href="?type=issuance" class="px-3 py-1.5 rounded-xl border <?= $eventType === 'issuance' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50/60 text-blue-800 border-blue-200 hover:bg-blue-100' ?>">
      <i class="fas fa-hand-holding-medical mr-1"></i> Releases / Issuances (<?= number_format($stats['issuances']) ?>)
    </a>
    <a href="?type=return" class="px-3 py-1.5 rounded-xl border <?= $eventType === 'return' ? 'bg-teal-600 text-white border-teal-600' : 'bg-teal-50/60 text-teal-800 border-teal-200 hover:bg-teal-100' ?>">
      <i class="fas fa-rotate-left mr-1"></i> Returns (<?= number_format($stats['returns']) ?>)
    </a>
    <a href="?type=adjustment" class="px-3 py-1.5 rounded-xl border <?= $eventType === 'adjustment' ? 'bg-purple-600 text-white border-purple-600' : 'bg-purple-50/60 text-purple-800 border-purple-200 hover:bg-purple-100' ?>">
      <i class="fas fa-sliders mr-1"></i> Adjustments (<?= number_format($stats['adjustments']) ?>)
    </a>
    <a href="?type=update" class="px-3 py-1.5 rounded-xl border <?= $eventType === 'update' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50/60 text-amber-800 border-amber-200 hover:bg-amber-100' ?>">
      <i class="fas fa-pen mr-1"></i> Stock Revisions
    </a>
    <a href="?type=deletion" class="px-3 py-1.5 rounded-xl border <?= $eventType === 'deletion' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50/60 text-rose-800 border-rose-200 hover:bg-rose-100' ?>">
      <i class="fas fa-trash-can mr-1"></i> Deletions & Reversals
    </a>
  </div>
</div>

<!-- KPI Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-clipboard-list"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Audit Transactions</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['total']) ?></div>
      <div class="text-xs text-slate-400">Total ledger entries</div>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-arrow-down-long"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Stock Additions</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['additions']) ?></div>
      <div class="text-xs text-slate-400">Batches & new supplies in</div>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-arrow-up-long"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Releases / Issuances</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['issuances']) ?></div>
      <div class="text-xs text-slate-400">Dispensed to patients/wards</div>
    </div>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
      <i class="fas fa-scale-balanced"></i>
    </div>
    <div class="min-w-0">
      <div class="text-xs uppercase font-medium tracking-wider text-slate-500">Adjustments & Returns</div>
      <div class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($stats['adjustments'] + $stats['returns']) ?></div>
      <div class="text-xs text-slate-400">Reconciliations & returns</div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
  <form method="get" action="/HealthLogs/public/inventory/audit_trail.php" class="space-y-4">
    <?php if (!empty($eventType)): ?>
      <input type="hidden" name="type" value="<?= h($eventType) ?>" />
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Search Keyword -->
      <div class="lg:col-span-2">
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Search Keywords</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
            <i class="fas fa-search text-xs"></i>
          </span>
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search medicine, batch #, reference, notes..." class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
        </div>
      </div>

      <!-- Medicine Selector -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Specific Medicine</label>
        <select name="medicine_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
          <option value="">All Medicines</option>
          <?php foreach ($medicinesList as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $medId === (int)$m['id'] ? 'selected' : '' ?>><?= h($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- User Selector -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Recorded By</label>
        <select name="user_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
          <option value="">All Staff</option>
          <?php foreach ($usersList as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $userFilter === (int)$u['id'] ? 'selected' : '' ?>>
              <?= h($u['username']) ?><?= $u['full_name'] ? ' (' . h($u['full_name']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Date Range & Submit -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-100">
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Date From</label>
        <input type="date" name="date_from" value="<?= h($dateFrom) ?>" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Date To</label>
        <input type="date" name="date_to" value="<?= h($dateTo) ?>" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <div class="flex items-end gap-2">
        <button type="submit" class="flex-1 bg-slate-900 text-white hover:bg-slate-800 px-4 py-2 rounded-xl text-sm font-semibold transition shadow-xs">
          <i class="fas fa-filter mr-1.5 text-xs"></i> Apply Filter
        </button>
        <a href="/HealthLogs/public/inventory/audit_trail.php" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition">
          Clear
        </a>
      </div>
    </div>
  </form>
</div>

<!-- Audit Trail Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-6">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="text-sm font-bold text-slate-900">Inventory Ledger Audit Trail</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600"><?= number_format($totalFiltered) ?> records</span>
    </div>
    <div class="text-xs text-slate-400">Page <?= $paginator->getCurrentPage() ?> of <?= max(1, $paginator->getTotalPages()) ?></div>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
          <th class="py-3.5 px-4">Date & Time</th>
          <th class="py-3.5 px-4">Medicine & Formulation</th>
          <th class="py-3.5 px-4">Batch #</th>
          <th class="py-3.5 px-4">Event Type</th>
          <th class="py-3.5 px-4">Stock Movement</th>
          <th class="py-3.5 px-4">Balance Impact</th>
          <th class="py-3.5 px-4">Reference</th>
          <th class="py-3.5 px-4">Performed By</th>
          <th class="py-3.5 px-4 text-right">Details</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm">
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="9" class="py-12 text-center text-slate-500">
              <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                <i class="fas fa-boxes-stacked"></i>
              </div>
              <p class="font-medium text-slate-700">No inventory audit records match the current filters.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <?php
              $details = !empty($log['details']) ? json_decode($log['details'], true) : [];
              $action = $log['action'];
              $qty = isset($details['quantity']) ? (int)$details['quantity'] : null;
              $unit = $details['unit'] ?? 'units';
              $medName = $details['medicine_name'] ?? ($details['name'] ?? null);
              $batchNo = $details['batch_no'] ?? null;
              $reference = $details['reference'] ?? null;
              $oldStock = isset($details['old_stock']) ? (int)$details['old_stock'] : null;
              $newStock = isset($details['new_stock']) ? (int)$details['new_stock'] : null;

              // Event label & badge styling
              $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
              $eventLabel = 'Inventory Event';
              $movementBadge = '';

              if (in_array($action, ['medicine_add', 'batch_add', 'stock_receive'])) {
                  $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                  $eventLabel = $action === 'medicine_add' ? 'Medicine Added' : 'Stock Received';
                  if ($qty !== null) {
                      $movementBadge = '<span class="inline-flex items-center text-xs font-bold text-emerald-700">+' . abs($qty) . ' ' . h($unit) . '</span>';
                  }
              } elseif ($action === 'issuance') {
                  $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                  $eventLabel = 'Released / Issued';
                  if ($qty !== null) {
                      $movementBadge = '<span class="inline-flex items-center text-xs font-bold text-rose-600">-' . abs($qty) . ' ' . h($unit) . '</span>';
                  }
              } elseif ($action === 'return') {
                  $badgeClass = 'bg-teal-50 text-teal-700 border-teal-200';
                  $eventLabel = 'Returned Stock';
                  if ($qty !== null) {
                      $movementBadge = '<span class="inline-flex items-center text-xs font-bold text-teal-700">+' . abs($qty) . ' ' . h($unit) . '</span>';
                  }
              } elseif ($action === 'adjustment') {
                  $badgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
                  $eventLabel = 'Inventory Adjustment';
                  if ($qty !== null) {
                      $sign = $qty >= 0 ? '+' : '';
                      $color = $qty >= 0 ? 'text-purple-700' : 'text-rose-600';
                      $movementBadge = "<span class='inline-flex items-center text-xs font-bold {$color}'>{$sign}{$qty} " . h($unit) . "</span>";
                  }
              } elseif (in_array($action, ['stock_update', 'batch_update', 'medicine_update'])) {
                  $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                  $eventLabel = 'Record Updated';
                  $movementBadge = '<span class="text-xs text-amber-700 font-medium">Modified</span>';
              } elseif (in_array($action, ['medicine_delete', 'batch_delete', 'transaction_delete'])) {
                  $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                  $eventLabel = 'Record Deleted';
                  $movementBadge = '<span class="text-xs text-rose-600 font-medium">Removed</span>';
              }
            ?>
            <tr class="hover:bg-slate-50/60 transition">
              <!-- Date & Time -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="text-xs font-semibold text-slate-800"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                <div class="text-[11px] text-slate-400"><?= date('h:i:s A', strtotime($log['created_at'])) ?></div>
              </td>

              <!-- Medicine -->
              <td class="py-3 px-4">
                <div class="font-semibold text-xs text-slate-900">
                  <?= h($medName ?: $log['description']) ?>
                </div>
                <?php if (!empty($details['formulation']) || !empty($details['strength'])): ?>
                  <div class="text-[11px] text-slate-400">
                    <?= h(trim(($details['formulation'] ?? '') . ' ' . ($details['strength'] ?? ''))) ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- Batch # -->
              <td class="py-3 px-4 whitespace-nowrap">
                <?php if (!empty($batchNo)): ?>
                  <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                    <?= h($batchNo) ?>
                  </span>
                <?php else: ?>
                  <span class="text-slate-300 text-xs">—</span>
                <?php endif; ?>
              </td>

              <!-- Event Type -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border <?= $badgeClass ?>">
                  <?= h($eventLabel) ?>
                </span>
              </td>

              <!-- Stock Movement -->
              <td class="py-3 px-4 whitespace-nowrap">
                <?= $movementBadge ?: '<span class="text-slate-400 text-xs">—</span>' ?>
              </td>

              <!-- Balance Impact -->
              <td class="py-3 px-4 whitespace-nowrap">
                <?php if ($oldStock !== null && $newStock !== null): ?>
                  <div class="flex items-center gap-1.5 text-xs font-mono">
                    <span class="text-slate-400"><?= number_format($oldStock) ?></span>
                    <i class="fas fa-arrow-right text-[10px] text-slate-300"></i>
                    <span class="font-bold text-slate-800"><?= number_format($newStock) ?></span>
                  </div>
                <?php else: ?>
                  <span class="text-slate-300 text-xs">—</span>
                <?php endif; ?>
              </td>

              <!-- Reference -->
              <td class="py-3 px-4 whitespace-nowrap">
                <?php if (!empty($reference)): ?>
                  <span class="text-xs font-mono text-slate-600 bg-slate-50 border border-slate-200/80 px-2 py-0.5 rounded">
                    <?= h($reference) ?>
                  </span>
                <?php else: ?>
                  <span class="text-slate-300 text-xs">—</span>
                <?php endif; ?>
              </td>

              <!-- Recorded By -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="text-xs font-semibold text-slate-800">
                  <?= h($log['full_name'] ?: ($log['username'] ?: 'System')) ?>
                </div>
                <div class="text-[11px] text-slate-400"><?= h(ucfirst($log['user_role'] ?: 'System')) ?></div>
              </td>

              <!-- Details Modal Trigger -->
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <button type="button" class="view-inventory-btn inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition"
                  data-log-id="<?= (int)$log['id'] ?>"
                  data-timestamp="<?= h($log['created_at']) ?>"
                  data-user="<?= h($log['full_name'] ?: ($log['username'] ?: 'System')) ?>"
                  data-role="<?= h($log['user_role'] ?: 'System') ?>"
                  data-action="<?= h($log['action']) ?>"
                  data-desc="<?= h($log['description']) ?>"
                  data-details='<?= htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8') ?>'>
                  <i class="fas fa-file-lines text-teal-600 text-xs"></i>
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

<!-- Inventory Details Modal -->
<div id="invModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" id="invModalBackdrop"></div>
  <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold">
            <i class="fas fa-boxes-stacked"></i>
          </div>
          <div>
            <div class="text-sm font-bold text-slate-900">Inventory Transaction Audit #<span id="invLogId"></span></div>
            <div class="text-xs text-slate-400" id="invLogTime"></div>
          </div>
        </div>
        <button type="button" id="invModalClose" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 flex items-center justify-center transition">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-4">
        <!-- Overview -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
          <div class="text-slate-400 uppercase font-semibold text-[10px]">Logged Action:</div>
          <div class="font-medium text-slate-900 text-sm mt-0.5" id="invDesc"></div>
          <div class="text-slate-500 mt-1">Logged by: <span class="font-semibold text-slate-800" id="invUser"></span> <span id="invRole" class="text-slate-400"></span></div>
        </div>

        <!-- Ledger Breakdown Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden">
          <div class="px-3.5 py-2 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            Audit Ledger Breakdown
          </div>
          <table class="min-w-full text-xs">
            <tbody id="invFieldsTable" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>

        <!-- Raw JSON -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Raw Payload</label>
          <pre class="bg-slate-950 text-emerald-400 p-3.5 rounded-xl text-xs font-mono overflow-x-auto max-h-44" id="invRawJson"></pre>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
        <button type="button" id="invModalCloseFooter" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const modal = document.getElementById('invModal');
  const backdrop = document.getElementById('invModalBackdrop');
  const closeBtn = document.getElementById('invModalClose');
  const closeFooter = document.getElementById('invModalCloseFooter');

  function closeModal() {
    modal.classList.add('hidden');
  }

  function openModal(data) {
    document.getElementById('invLogId').textContent = data.logId;
    document.getElementById('invLogTime').textContent = data.timestamp;
    document.getElementById('invUser').textContent = data.user;
    document.getElementById('invRole').textContent = '(' + data.role + ')';
    document.getElementById('invDesc').textContent = data.desc;

    let parsed = {};
    try {
      parsed = JSON.parse(data.details);
    } catch(e) {
      parsed = data.details || {};
    }

    const table = document.getElementById('invFieldsTable');
    table.innerHTML = '';

    for (const [key, val] of Object.entries(parsed)) {
      if (typeof val === 'object' && val !== null) continue;
      const tr = document.createElement('tr');
      const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
      tr.innerHTML = `
        <td class="py-2 px-3 font-semibold text-slate-500 w-1/3 bg-slate-50/60">${formattedKey}</td>
        <td class="py-2 px-3 text-slate-900 font-mono text-[11px]">${val !== null ? String(val) : '<span class="text-slate-400">null</span>'}</td>
      `;
      table.appendChild(tr);
    }

    document.getElementById('invRawJson').textContent = JSON.stringify(parsed, null, 2);
    modal.classList.remove('hidden');
  }

  document.querySelectorAll('.view-inventory-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      openModal({
        logId: this.dataset.logId,
        timestamp: this.dataset.timestamp,
        user: this.dataset.user,
        role: this.dataset.role,
        action: this.dataset.action,
        desc: this.dataset.desc,
        details: this.dataset.details
      });
    });
  });

  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (closeFooter) closeFooter.addEventListener('click', closeModal);
})();
</script>

<?php require __DIR__ . '/../../public/partials/footer.php'; ?>
