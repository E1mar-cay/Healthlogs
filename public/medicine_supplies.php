<?php
$pageTitle = 'Medicine Supply Monitoring';
require __DIR__ . '/partials/header.php';

$suppliesSummary = [
    'items' => 0,
    'batches' => 0,
    'low_stock' => 0,
    'movements' => 0,
    'valid_units' => 0,
];

$recentTransactions = [];
$stockAlerts = [];
$suppliesList = [];
$categoryStats = [
    'all' => ['label' => 'All Supplies', 'count' => 0, 'valid_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
    'oral' => ['label' => 'Oral Solids', 'count' => 0, 'valid_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
    'liquids' => ['label' => 'Syrups & Suspensions', 'count' => 0, 'valid_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
    'supplies' => ['label' => 'Medical Consumables', 'count' => 0, 'valid_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
];
$alertCounts = [
    'all' => 0,
    'low_stock' => 0,
    'near_expiry' => 0,
    'expired' => 0,
];

try {
    $suppliesSummary['items'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
    $suppliesSummary['batches'] = (int)$pdo->query("SELECT COUNT(*) FROM medicine_batches")->fetchColumn();
    $suppliesSummary['movements'] = (int)$pdo->query("SELECT COUNT(*) FROM medicine_transactions")->fetchColumn();
    $suppliesSummary['low_stock'] = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT m.id
            FROM medicines m
            LEFT JOIN (
                SELECT mb.medicine_id, SUM(mt.quantity) AS valid_qty
                FROM medicine_batches mb
                JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.expiry_date >= CURDATE()
                GROUP BY mb.medicine_id
            ) stk ON stk.medicine_id = m.id
            GROUP BY m.id, m.reorder_level
            HAVING COALESCE(MAX(stk.valid_qty), 0) <= COALESCE(m.reorder_level, 0)
        ) low_stock_medicines
    ")->fetchColumn();

    $recentTransactions = $pdo->query("
        SELECT t.transaction_datetime, t.transaction_type, t.quantity, t.reference, m.name AS medicine_name
        FROM medicine_transactions t
        JOIN medicines m ON m.id = t.medicine_id
        ORDER BY t.transaction_datetime DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch stock attention alerts
    $stockAlerts = $pdo->query("
        SELECT * FROM (
            SELECT
                'expired' AS alert_type,
                CONCAT(m.name, ' (Batch: ', b.batch_no, ')') AS title,
                CONCAT('Expired and barred from dispensing | Quarantined on hand: ', COALESCE(SUM(t.quantity), 0)) AS details,
                b.expiry_date AS alert_date,
                0 AS sort_priority,
                COALESCE(SUM(t.quantity), 0) AS sort_value,
                m.name AS medicine_name,
                b.batch_no
            FROM medicine_batches b
            JOIN medicines m ON m.id = b.medicine_id
            LEFT JOIN medicine_transactions t ON t.batch_id = b.id
            WHERE b.expiry_date < CURDATE()
            GROUP BY b.id, m.name, b.batch_no, b.expiry_date
            HAVING COALESCE(SUM(t.quantity), 0) > 0

            UNION ALL

            SELECT
                'near_expiry' AS alert_type,
                CONCAT(m.name, ' (Batch: ', b.batch_no, ')') AS title,
                CONCAT('Approaching expiration (', DATEDIFF(b.expiry_date, CURDATE()), ' days left) | On hand: ', COALESCE(SUM(t.quantity), 0)) AS details,
                b.expiry_date AS alert_date,
                1 AS sort_priority,
                COALESCE(SUM(t.quantity), 0) AS sort_value,
                m.name AS medicine_name,
                b.batch_no
            FROM medicine_batches b
            JOIN medicines m ON m.id = b.medicine_id
            LEFT JOIN medicine_transactions t ON t.batch_id = b.id
            WHERE b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
            GROUP BY b.id, m.name, b.batch_no, b.expiry_date
            HAVING COALESCE(SUM(t.quantity), 0) > 0

            UNION ALL

            SELECT
                'low_stock' AS alert_type,
                m.name AS title,
                CONCAT('Available on hand: ', COALESCE(MAX(stk.valid_qty), 0), ' | Reorder threshold: ', COALESCE(m.reorder_level, 0)) AS details,
                NULL AS alert_date,
                2 AS sort_priority,
                COALESCE(MAX(stk.valid_qty), 0) AS sort_value,
                m.name AS medicine_name,
                NULL AS batch_no
            FROM medicines m
            LEFT JOIN (
                SELECT mb.medicine_id, SUM(mt.quantity) AS valid_qty
                FROM medicine_batches mb
                JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.expiry_date >= CURDATE()
                GROUP BY mb.medicine_id
            ) stk ON stk.medicine_id = m.id
            GROUP BY m.id, m.name, m.reorder_level
            HAVING COALESCE(MAX(stk.valid_qty), 0) <= COALESCE(m.reorder_level, 0)
        ) alerts
        ORDER BY
            sort_priority ASC,
            COALESCE(alert_date, '9999-12-31') ASC,
            sort_value ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stockAlerts as $alert) {
        $alertCounts['all']++;
        if (isset($alertCounts[$alert['alert_type']])) {
            $alertCounts[$alert['alert_type']]++;
        }
    }

    // Fetch comprehensive supplies catalog with physical stock calculations
    $suppliesStmt = $pdo->query("
        SELECT 
            m.id,
            m.name,
            m.generic_name,
            m.formulation,
            m.strength,
            m.unit,
            COALESCE(m.reorder_level, 100) AS reorder_level,
            COALESCE(stk.valid_stock, 0) AS valid_stock,
            COALESCE(exp_stk.expired_stock, 0) AS expired_stock,
            COALESCE(tot.total_stock, 0) AS total_stock,
            COALESCE(b_count.batch_count, 0) AS batch_count
        FROM medicines m
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(mt.quantity) AS valid_stock
            FROM medicine_batches mb
            JOIN medicine_transactions mt ON mt.batch_id = mb.id
            WHERE mb.expiry_date >= CURDATE()
            GROUP BY mb.medicine_id
        ) stk ON stk.medicine_id = m.id
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(mt.quantity) AS expired_stock
            FROM medicine_batches mb
            JOIN medicine_transactions mt ON mt.batch_id = mb.id
            WHERE mb.expiry_date < CURDATE()
            GROUP BY mb.medicine_id
        ) exp_stk ON exp_stk.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, SUM(quantity) AS total_stock
            FROM medicine_transactions
            GROUP BY medicine_id
        ) tot ON tot.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, COUNT(*) AS batch_count
            FROM medicine_batches
            GROUP BY medicine_id
        ) b_count ON b_count.medicine_id = m.id
        ORDER BY m.name ASC, m.formulation ASC
    ");
    $rawSupplies = $suppliesStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawSupplies as $row) {
        $f = strtolower(trim((string)$row['formulation']));
        if (in_array($f, ['tablet', 'capsule'], true)) {
            $cat = 'oral';
            $catLabel = 'Oral Solid';
        } elseif (in_array($f, ['syrup', 'suspension', 'drops', 'solution'], true)) {
            $cat = 'liquids';
            $catLabel = 'Syrups & Suspensions';
        } else {
            $cat = 'supplies';
            $catLabel = 'Medical Consumables';
        }

        $valid = (int)$row['valid_stock'];
        $reorder = (int)$row['reorder_level'];
        $expired = (int)$row['expired_stock'];

        if ($valid <= 0) {
            $statusKey = 'out_of_stock';
            $statusLabel = 'Out of Stock';
            $statusBadge = 'bg-rose-100 text-rose-800 border border-rose-300';
            $statusIcon = 'fa-circle-xmark text-rose-600';
        } elseif ($valid <= $reorder) {
            $statusKey = 'low_stock';
            $statusLabel = 'Low Stock';
            $statusBadge = 'bg-amber-100 text-amber-800 border border-amber-300';
            $statusIcon = 'fa-triangle-exclamation text-amber-600';
        } else {
            $statusKey = 'healthy';
            $statusLabel = 'Healthy';
            $statusBadge = 'bg-emerald-100 text-emerald-800 border border-emerald-300';
            $statusIcon = 'fa-circle-check text-emerald-600';
        }

        $row['category_key'] = $cat;
        $row['category_label'] = $catLabel;
        $row['status_key'] = $statusKey;
        $row['status_label'] = $statusLabel;
        $row['status_badge'] = $statusBadge;
        $row['status_icon'] = $statusIcon;
        $row['has_expired'] = ($expired > 0);

        $suppliesList[] = $row;

        // Statistics aggregates
        $categoryStats['all']['count']++;
        $categoryStats['all']['valid_stock'] += $valid;
        if ($statusKey === 'low_stock') $categoryStats['all']['low_stock']++;
        if ($statusKey === 'out_of_stock') $categoryStats['all']['out_of_stock']++;

        $categoryStats[$cat]['count']++;
        $categoryStats[$cat]['valid_stock'] += $valid;
        if ($statusKey === 'low_stock') $categoryStats[$cat]['low_stock']++;
        if ($statusKey === 'out_of_stock') $categoryStats[$cat]['out_of_stock']++;
    }

    $suppliesSummary['valid_units'] = $categoryStats['all']['valid_stock'];
} catch (Throwable $e) {
    // Keep page functional
}
?>

<!-- Header Banner matching immunization.php theme -->
<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Stock on hand & batch tracking</div>
      <div class="text-2xl font-semibold">Medicine Supply Module</div>
      <p class="text-sm text-slate-500 mt-1">Track physical stock on hand, incoming shipment batches, lot expiry dates, and remaining balances per supply.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="batchModalOpenNew" data-embed-url="/HealthLogs/public/inventory/batches/form_embed.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-teal-600 text-white hover:bg-teal-700 text-xs font-semibold shadow-xs transition">
          <i class="fas fa-plus-circle"></i>
          <span>Receive Batch</span>
        </button>
        <button type="button" id="transactionModalOpenNew" data-embed-url="/HealthLogs/public/inventory/transactions/form_embed.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-xs font-semibold shadow-xs transition">
          <i class="fas fa-right-left"></i>
          <span>Record Movement</span>
        </button>
      <?php endif; ?>
      <a href="/HealthLogs/public/inventory.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" title="Go to Medicine Catalog">
        <i class="fas fa-book-medical"></i>
        <span>Medicine Catalog</span>
      </a>
    </div>
  </div>
</div>

<!-- 4 Navigation Cards matching immunization.php -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/batches/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-lime-100 text-lime-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="4" y="4" width="16" height="16" rx="2"></rect>
          <path d="M4 9h16"></path>
          <path d="M9 4v16"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Supplies</div>
        <div class="text-lg font-semibold">Tracked Batches</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Inspect shipment lots, expiry dates, and quarantined balances.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/transactions/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 6h16"></path>
          <path d="M7 10h10"></path>
          <path d="M9 14h6"></path>
          <path d="M11 18h2"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Movements</div>
        <div class="text-lg font-semibold">Transactions</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Log incoming supply receipts, patient dispensations, and adjustments.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/audit_trail.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <i class="fas fa-clipboard-check text-xl"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">Security</div>
        <div class="text-lg font-semibold">Audit Trail</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Permanent inventory ledger of additions, returns & write-offs.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M7 3h10v6H7z"></path>
          <path d="M5 9h14v12H5z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Catalog</div>
        <div class="text-lg font-semibold">Medicine Inventory</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Configure medicine profiles, brand names, formulations, and units.</p>
  </a>
</div>

<!-- 4 KPI Summary Cards matching immunization.php -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Units On Hand</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($suppliesSummary['valid_units'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Valid units across all supplies</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active Batches</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($suppliesSummary['batches'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Tracked delivery lots</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Low Stock</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($suppliesSummary['low_stock'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Supplies below reorder point</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Movements</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($suppliesSummary['movements'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Logged transaction entries</div>
  </div>
</div>

<!-- SECTION: Physical Stock Per Supply Breakdown -->
<div id="supplies-catalog" class="bg-white p-6 rounded shadow mt-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b">
    <div>
      <div class="text-sm text-slate-500">Physical inventory & remaining balances</div>
      <div class="text-xl font-semibold text-slate-900">Stock Per Medicine Supply</div>
      <p class="text-sm text-slate-500 mt-1">Available on-hand balances separated by category, reorder thresholds, and active lot tracking.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">Oral: <?= $categoryStats['oral']['count'] ?> (<?= number_format($categoryStats['oral']['valid_stock']) ?> units)</span>
      <span class="app-chip">Liquids: <?= $categoryStats['liquids']['count'] ?> (<?= number_format($categoryStats['liquids']['valid_stock']) ?> units)</span>
      <span class="app-chip">Consumables: <?= $categoryStats['supplies']['count'] ?> (<?= number_format($categoryStats['supplies']['valid_stock']) ?> units)</span>
    </div>
  </div>

  <!-- Category Selection & Search Controls -->
  <div class="mt-4 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
    <!-- Category Tabs -->
    <div class="flex flex-wrap items-center gap-2">
      <button type="button" class="supply-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-900 text-white transition" data-category="all">
        All Supplies (<?= count($suppliesList) ?>)
      </button>
      <button type="button" class="supply-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-category="oral">
        Oral Solids (<?= $categoryStats['oral']['count'] ?>)
      </button>
      <button type="button" class="supply-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-category="liquids">
        Syrups & Suspensions (<?= $categoryStats['liquids']['count'] ?>)
      </button>
      <button type="button" class="supply-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-category="supplies">
        Medical Consumables (<?= $categoryStats['supplies']['count'] ?>)
      </button>
    </div>

    <!-- Status Filter Buttons -->
    <div class="flex flex-wrap items-center gap-1.5 text-xs">
      <span class="text-slate-500 font-medium mr-1">Status:</span>
      <button type="button" class="supply-status-btn px-2.5 py-1 rounded text-xs font-semibold bg-slate-900 text-white transition" data-status="all">
        All
      </button>
      <button type="button" class="supply-status-btn px-2.5 py-1 rounded text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition" data-status="healthy">
        Healthy
      </button>
      <button type="button" class="supply-status-btn px-2.5 py-1 rounded text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition" data-status="low_stock">
        Low Stock
      </button>
      <button type="button" class="supply-status-btn px-2.5 py-1 rounded text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition" data-status="out_of_stock">
        Out of Stock
      </button>
      <button type="button" class="supply-status-btn px-2.5 py-1 rounded text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition" data-status="has_expired">
        Expired Lots
      </button>
    </div>
  </div>

  <!-- Search Input Row -->
  <div class="mt-3 flex flex-col md:flex-row items-center justify-between gap-3">
    <div class="relative w-full md:w-96">
      <input type="text" id="suppliesSearchInput" placeholder="Search by name, generic, formulation, or strength..." class="w-full border border-slate-300 rounded px-3 py-2 text-xs focus:outline-hidden focus:border-slate-500" />
      <button type="button" id="suppliesSearchClear" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs">
        <i class="fas fa-times-circle"></i>
      </button>
    </div>

    <div class="flex items-center gap-2 text-xs text-slate-500 self-end md:self-auto">
      <label for="suppliesPerPage" class="font-medium">Rows per page:</label>
      <select id="suppliesPerPage" class="border border-slate-300 rounded px-2 py-1 text-xs bg-white text-slate-700">
        <option value="15" selected>15</option>
        <option value="30">30</option>
        <option value="50">50</option>
        <option value="all">All</option>
      </select>
    </div>
  </div>

  <!-- Supplies Table -->
  <div class="mt-4 bg-white rounded border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm" id="suppliesTable">
      <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
        <tr>
          <th class="text-left px-4 py-3">Medicine Supply & Generic</th>
          <th class="text-left px-4 py-3">Category & Formulation</th>
          <th class="text-right px-4 py-3">Available On Hand</th>
          <th class="text-center px-4 py-3">Reorder Point</th>
          <th class="text-left px-4 py-3">Stock Health</th>
          <th class="text-center px-4 py-3">Batches</th>
          <th class="text-center px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-slate-700" id="suppliesTableBody">
        <?php if (empty($suppliesList)): ?>
          <tr>
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
              No medicine supplies found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($suppliesList as $item): ?>
            <?php
              $valid = (int)$item['valid_stock'];
              $reorder = (int)$item['reorder_level'];
              $expired = (int)$item['expired_stock'];
              $hasExpired = $item['has_expired'];
            ?>
            <tr class="supply-row hover:bg-slate-50 transition"
                data-category="<?= h($item['category_key']) ?>"
                data-status="<?= h($item['status_key']) ?>"
                data-has-expired="<?= $hasExpired ? '1' : '0' ?>"
                data-search="<?= h(strtolower($item['name'] . ' ' . $item['generic_name'] . ' ' . $item['formulation'] . ' ' . $item['strength'] . ' ' . $item['unit'])) ?>">
              
              <!-- Supply & Generic Name -->
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900 flex items-center gap-1.5">
                  <span><?= h($item['name']) ?></span>
                  <?php if (!empty($item['strength'])): ?>
                    <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200 font-normal">
                      <?= h($item['strength']) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($item['generic_name'])): ?>
                  <div class="text-xs text-slate-500 mt-0.5"><?= h($item['generic_name']) ?></div>
                <?php endif; ?>
              </td>

              <!-- Category & Formulation -->
              <td class="px-4 py-3 whitespace-nowrap text-xs">
                <div class="font-medium text-slate-800"><?= h($item['category_label']) ?></div>
                <div class="text-slate-500 mt-0.5"><?= h($item['formulation'] ?: 'Standard') ?> (<?= h($item['unit']) ?>)</div>
              </td>

              <!-- Available On Hand -->
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <div class="font-semibold text-sm <?= $valid <= 0 ? 'text-red-600' : ($valid <= $reorder ? 'text-amber-700' : 'text-slate-900') ?>">
                  <?= h(number_format($valid)) ?>
                  <span class="text-xs font-normal text-slate-500"><?= h($item['unit']) ?></span>
                </div>
              </td>

              <!-- Reorder Point -->
              <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-slate-600">
                <?= h(number_format($reorder)) ?> <?= h($item['unit']) ?>
              </td>

              <!-- Stock Health -->
              <td class="px-4 py-3 whitespace-nowrap text-xs">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium <?= $item['status_badge'] ?>">
                  <i class="fas <?= $item['status_icon'] ?>"></i>
                  <span><?= h($item['status_label']) ?></span>
                </span>
                <?php if ($expired > 0): ?>
                  <div class="mt-1">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200">
                      <i class="fas fa-ban"></i> <?= h(number_format($expired)) ?> <?= h($item['unit']) ?> quarantined
                    </span>
                  </div>
                <?php endif; ?>
              </td>

              <!-- Batches -->
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <a href="/HealthLogs/public/inventory/batches/index.php?q=<?= urlencode($item['name']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition" title="View batches">
                  <i class="fas fa-tag text-[10px] text-slate-400"></i>
                  <span><?= (int)$item['batch_count'] ?></span>
                </a>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-center whitespace-nowrap text-xs">
                <div class="inline-flex items-center gap-2">
                  <a href="/HealthLogs/public/inventory/batches/index.php?q=<?= urlencode($item['name']) ?>" class="text-blue-600 hover:underline">
                    Batches
                  </a>
                  <span class="text-slate-300">|</span>
                  <a href="/HealthLogs/public/inventory/transactions/index.php?q=<?= urlencode($item['name']) ?>" class="text-slate-600 hover:text-slate-900 hover:underline">
                    History
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div id="suppliesEmptyState" class="hidden py-8 text-center text-slate-500 text-sm">
      No medicine supplies match your filter or search keyword.
      <div class="mt-2">
        <button type="button" id="suppliesResetBtn" class="px-3 py-1 bg-slate-900 text-white rounded text-xs hover:bg-slate-800 transition">
          Reset Filters
        </button>
      </div>
    </div>
  </div>

  <!-- Pagination & Counter -->
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 text-xs text-slate-500">
    <div id="suppliesShowingCount">Showing supplies...</div>
    <div class="flex items-center gap-1" id="suppliesPagination"></div>
  </div>
</div>

<!-- Two-Column Activity & Snapshot matching immunization.php -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <!-- Recent Activity -->
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Recent Movement</div>
    <div class="text-lg font-semibold">Latest Stock Transactions</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($recentTransactions)): ?>
        <div class="text-sm text-slate-500">No recent supply movements recorded.</div>
      <?php else: ?>
        <?php foreach ($recentTransactions as $transaction): ?>
          <?php $qty = (int)$transaction['quantity']; ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($transaction['medicine_name']) ?></div>
                <div class="text-sm text-slate-500 mt-1"><?= h(ucfirst($transaction['transaction_type'])) ?><?= $transaction['reference'] ? ' | ' . h($transaction['reference']) : '' ?></div>
              </div>
              <div class="text-sm font-semibold <?= $qty < 0 ? 'text-red-600' : 'text-emerald-700' ?>">
                <?= h(($qty < 0 ? '-' : '+') . abs($qty)) ?>
              </div>
            </div>
            <div class="text-xs text-slate-400 mt-2"><?= h($transaction['transaction_datetime']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stock Attention Snapshot -->
  <div class="bg-white p-5 rounded shadow">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <div>
        <div class="text-sm text-slate-500">Summary</div>
        <div class="text-lg font-semibold">Stock Attention Snapshot</div>
      </div>
      <div class="text-xs text-slate-400 font-medium">
        <?= count($stockAlerts) ?> alerts
      </div>
    </div>

    <!-- Alert Filter Pills -->
    <div class="flex flex-wrap items-center gap-1.5 mt-3 pb-3 border-b border-slate-100">
      <button type="button" class="alert-filter-btn px-2.5 py-1 text-xs font-semibold rounded bg-slate-900 text-white transition" data-alert-type="all">
        All (<?= $alertCounts['all'] ?>)
      </button>
      <button type="button" class="alert-filter-btn px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-alert-type="low_stock">
        Low Stock (<?= $alertCounts['low_stock'] ?>)
      </button>
      <button type="button" class="alert-filter-btn px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-alert-type="near_expiry">
        Near Expiry (<?= $alertCounts['near_expiry'] ?>)
      </button>
      <button type="button" class="alert-filter-btn px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-alert-type="expired">
        Expired Lots (<?= $alertCounts['expired'] ?>)
      </button>
    </div>

    <div class="mt-4 space-y-3 max-h-[380px] overflow-y-auto pr-1" id="alertsListContainer">
      <?php if (empty($stockAlerts)): ?>
        <div class="text-sm text-slate-500">No supply stock alerts found. All items healthy.</div>
      <?php else: ?>
        <?php foreach ($stockAlerts as $alert): ?>
          <?php
            $badgeStyle = 'bg-amber-100 text-amber-800 border border-amber-300';
            $badgeText = 'Low Stock';
            $actionLink = '/HealthLogs/public/inventory/medicines/index.php?q=' . urlencode($alert['medicine_name']);

            if ($alert['alert_type'] === 'expired') {
                $badgeStyle = 'bg-rose-100 text-rose-800 border border-rose-300 font-bold';
                $badgeText = 'Expired (Barred)';
                $actionLink = '/HealthLogs/public/inventory/batches/index.php?q=' . urlencode($alert['medicine_name']);
            } elseif ($alert['alert_type'] === 'near_expiry') {
                $badgeStyle = 'bg-amber-100 text-amber-800 border border-amber-300 font-bold';
                $badgeText = 'Near Expiry';
                $actionLink = '/HealthLogs/public/inventory/batches/index.php?q=' . urlencode($alert['medicine_name']);
            }
          ?>
          <div class="alert-item rounded-xl border border-slate-200 bg-slate-50 p-4" data-alert-type="<?= h($alert['alert_type']) ?>">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($alert['title']) ?></div>
                <div class="text-sm text-slate-500 mt-1"><?= h($alert['details']) ?></div>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs whitespace-nowrap <?= $badgeStyle ?>">
                <?= h($badgeText) ?>
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-400 mt-2">
              <span>
                <?php if (!empty($alert['alert_date'])): ?>
                  Expiry: <?= h($alert['alert_date']) ?>
                <?php else: ?>
                  Under reorder threshold
                <?php endif; ?>
              </span>
              <a href="<?= $actionLink ?>" class="text-blue-600 hover:underline font-medium">
                Manage
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div id="alertsEmptyState" class="hidden py-8 text-center text-slate-500 text-sm">
      No alerts found in this category.
    </div>
  </div>
</div>

<!-- Embedded Modals for direct supply actions (Receive Batch & Record Transaction) -->
<div id="batchFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="batchFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-5xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Receive supply batch</div>
        <button type="button" id="batchFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="batchFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Receive supply batch form"></iframe>
    </div>
  </div>
</div>

<div id="transactionFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="transactionFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Record stock transaction</div>
        <button type="button" id="transactionFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="transactionFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Transaction form"></iframe>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Modal handling for direct actions on this page
  var batchModal = document.getElementById('batchFormModal');
  var batchFrame = document.getElementById('batchFormModalFrame');
  var batchBackdrop = document.getElementById('batchFormModalBackdrop');
  var batchClose = document.getElementById('batchFormModalClose');

  var txModal = document.getElementById('transactionFormModal');
  var txFrame = document.getElementById('transactionFormModalFrame');
  var txBackdrop = document.getElementById('transactionFormModalBackdrop');
  var txClose = document.getElementById('transactionFormModalClose');

  function openFrame(modal, frame, url) {
    if (!modal || !frame || !url) return;
    frame.src = url;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeFrame(modal, frame) {
    if (!modal || !frame) return;
    frame.src = 'about:blank';
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  var newBatchBtn = document.getElementById('batchModalOpenNew');
  if (newBatchBtn) {
    newBatchBtn.addEventListener('click', function() {
      openFrame(batchModal, batchFrame, newBatchBtn.getAttribute('data-embed-url') || '');
    });
  }
  if (batchBackdrop) batchBackdrop.addEventListener('click', function() { closeFrame(batchModal, batchFrame); });
  if (batchClose) batchClose.addEventListener('click', function() { closeFrame(batchModal, batchFrame); });

  var newTxBtn = document.getElementById('transactionModalOpenNew');
  if (newTxBtn) {
    newTxBtn.addEventListener('click', function() {
      openFrame(txModal, txFrame, newTxBtn.getAttribute('data-embed-url') || '');
    });
  }
  if (txBackdrop) txBackdrop.addEventListener('click', function() { closeFrame(txModal, txFrame); });
  if (txClose) txClose.addEventListener('click', function() { closeFrame(txModal, txFrame); });

  // Alert Tabs Filtering
  const alertBtns = document.querySelectorAll('.alert-filter-btn');
  const alertItems = document.querySelectorAll('.alert-item');
  const alertsEmpty = document.getElementById('alertsEmptyState');

  alertBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const type = this.getAttribute('data-alert-type');

      alertBtns.forEach(b => {
        b.classList.remove('bg-slate-900', 'text-white');
        b.classList.add('bg-slate-100', 'text-slate-700');
      });
      this.classList.remove('bg-slate-100', 'text-slate-700');
      this.classList.add('bg-slate-900', 'text-white');

      let visibleCount = 0;
      alertItems.forEach(item => {
        const itemType = item.getAttribute('data-alert-type');
        if (type === 'all' || itemType === type) {
          item.style.display = '';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });

      if (alertsEmpty) {
        alertsEmpty.classList.toggle('hidden', visibleCount > 0);
      }
    });
  });

  // Supplies Table Engine
  const supplyCatBtns = document.querySelectorAll('.supply-cat-btn');
  const supplyStatusBtns = document.querySelectorAll('.supply-status-btn');
  const searchInput = document.getElementById('suppliesSearchInput');
  const searchClear = document.getElementById('suppliesSearchClear');
  const perPageSelect = document.getElementById('suppliesPerPage');
  const tableRows = Array.from(document.querySelectorAll('.supply-row'));
  const emptyState = document.getElementById('suppliesEmptyState');
  const showingCount = document.getElementById('suppliesShowingCount');
  const paginationContainer = document.getElementById('suppliesPagination');
  const resetBtn = document.getElementById('suppliesResetBtn');

  let currentCategory = 'all';
  let currentStatus = 'all';
  let currentSearch = '';
  let currentPage = 1;
  let itemsPerPage = 15;

  function filterAndPaginate() {
    let matchingRows = [];

    tableRows.forEach(row => {
      const cat = row.getAttribute('data-category');
      const status = row.getAttribute('data-status');
      const hasExpired = row.getAttribute('data-has-expired') === '1';
      const searchText = row.getAttribute('data-search') || '';

      const matchCat = (currentCategory === 'all' || cat === currentCategory);

      let matchStatus = false;
      if (currentStatus === 'all') {
        matchStatus = true;
      } else if (currentStatus === 'has_expired') {
        matchStatus = hasExpired;
      } else {
        matchStatus = (status === currentStatus);
      }

      const matchSearch = (currentSearch === '' || searchText.includes(currentSearch));

      if (matchCat && matchStatus && matchSearch) {
        matchingRows.push(row);
      } else {
        row.style.display = 'none';
      }
    });

    const totalMatches = matchingRows.length;

    if (emptyState) {
      emptyState.classList.toggle('hidden', totalMatches > 0);
    }

    const limit = (itemsPerPage === 'all') ? totalMatches : parseInt(itemsPerPage, 10);
    const totalPages = (limit > 0 && totalMatches > 0) ? Math.ceil(totalMatches / limit) : 1;

    if (currentPage > totalPages) {
      currentPage = 1;
    }

    const startIndex = (limit === totalMatches) ? 0 : (currentPage - 1) * limit;
    const endIndex = (limit === totalMatches) ? totalMatches : Math.min(startIndex + limit, totalMatches);

    matchingRows.forEach((row, idx) => {
      if (idx >= startIndex && idx < endIndex) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });

    if (showingCount) {
      if (totalMatches === 0) {
        showingCount.textContent = 'Showing 0 of 0 supplies';
      } else {
        showingCount.innerHTML = `Showing <strong>${startIndex + 1}</strong> to <strong>${endIndex}</strong> of <strong>${totalMatches}</strong> supplies (from ${tableRows.length} total)`;
      }
    }

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    if (!paginationContainer) return;
    paginationContainer.innerHTML = '';

    if (totalPages <= 1) return;

    // Prev Button
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = `px-2.5 py-1 rounded text-xs border border-slate-300 ${currentPage === 1 ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-50'}`;
    prevBtn.innerHTML = '<i class="fas fa-chevron-left text-[10px]"></i>';
    prevBtn.disabled = (currentPage === 1);
    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        filterAndPaginate();
      }
    });
    paginationContainer.appendChild(prevBtn);

    // Page Numbers
    for (let p = 1; p <= totalPages; p++) {
      if (totalPages > 7) {
        if (p !== 1 && p !== totalPages && Math.abs(p - currentPage) > 2) {
          if (p === 2 || p === totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-1 text-slate-400';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
          }
          continue;
        }
      }

      const pageBtn = document.createElement('button');
      pageBtn.type = 'button';
      pageBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${p === currentPage ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50'}`;
      pageBtn.textContent = p;
      pageBtn.addEventListener('click', () => {
        currentPage = p;
        filterAndPaginate();
      });
      paginationContainer.appendChild(pageBtn);
    }

    // Next Button
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = `px-2.5 py-1 rounded text-xs border border-slate-300 ${currentPage === totalPages ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-50'}`;
    nextBtn.innerHTML = '<i class="fas fa-chevron-right text-[10px]"></i>';
    nextBtn.disabled = (currentPage === totalPages);
    nextBtn.addEventListener('click', () => {
      if (currentPage < totalPages) {
        currentPage++;
        filterAndPaginate();
      }
    });
    paginationContainer.appendChild(nextBtn);
  }

  // Category Tab Click
  supplyCatBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      currentCategory = this.getAttribute('data-category');
      currentPage = 1;

      supplyCatBtns.forEach(b => {
        b.classList.remove('bg-slate-900', 'text-white');
        b.classList.add('bg-slate-100', 'text-slate-700');
      });
      this.classList.remove('bg-slate-100', 'text-slate-700');
      this.classList.add('bg-slate-900', 'text-white');

      filterAndPaginate();
    });
  });

  // Status Filter Click
  supplyStatusBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      currentStatus = this.getAttribute('data-status');
      currentPage = 1;

      supplyStatusBtns.forEach(b => {
        b.classList.remove('bg-slate-900', 'text-white');
        b.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-300');
      });
      this.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-300');
      this.classList.add('bg-slate-900', 'text-white');

      filterAndPaginate();
    });
  });

  // Search Input
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      currentSearch = this.value.trim().toLowerCase();
      currentPage = 1;
      if (searchClear) {
        searchClear.classList.toggle('hidden', currentSearch === '');
      }
      filterAndPaginate();
    });
  }

  if (searchClear) {
    searchClear.addEventListener('click', function() {
      if (searchInput) {
        searchInput.value = '';
        currentSearch = '';
        this.classList.add('hidden');
        currentPage = 1;
        filterAndPaginate();
      }
    });
  }

  if (perPageSelect) {
    perPageSelect.addEventListener('change', function() {
      itemsPerPage = this.value;
      currentPage = 1;
      filterAndPaginate();
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', function() {
      currentCategory = 'all';
      currentStatus = 'all';
      currentSearch = '';
      currentPage = 1;

      if (searchInput) {
        searchInput.value = '';
      }
      if (searchClear) {
        searchClear.classList.add('hidden');
      }

      supplyCatBtns.forEach((b, idx) => {
        if (idx === 0) {
          b.classList.add('bg-slate-900', 'text-white');
          b.classList.remove('bg-slate-100', 'text-slate-700');
        } else {
          b.classList.remove('bg-slate-900', 'text-white');
          b.classList.add('bg-slate-100', 'text-slate-700');
        }
      });

      supplyStatusBtns.forEach((b, idx) => {
        if (idx === 0) {
          b.classList.add('bg-slate-900', 'text-white');
          b.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-300');
        } else {
          b.classList.remove('bg-slate-900', 'text-white');
          b.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-300');
        }
      });

      filterAndPaginate();
    });
  }

  filterAndPaginate();
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
