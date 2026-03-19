<?php
$pageTitle = 'Medicine Inventory Module';
require __DIR__ . '/partials/header.php';

$inventorySummary = [
    'medicines' => 0,
    'batches' => 0,
    'low_stock' => 0,
    'movements' => 0,
];

$recentTransactions = [];
$stockAlerts = [];

try {
    $inventorySummary['medicines'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
    $inventorySummary['batches'] = (int)$pdo->query("SELECT COUNT(*) FROM medicine_batches")->fetchColumn();
    $inventorySummary['movements'] = (int)$pdo->query("SELECT COUNT(*) FROM medicine_transactions")->fetchColumn();
    $inventorySummary['low_stock'] = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT m.id
            FROM medicines m
            LEFT JOIN medicine_transactions t ON t.medicine_id = m.id
            GROUP BY m.id, m.reorder_level
            HAVING COALESCE(SUM(t.quantity), 0) <= COALESCE(m.reorder_level, 0)
        ) low_stock_medicines
    ")->fetchColumn();

    $recentTransactions = $pdo->query("
        SELECT t.transaction_datetime, t.transaction_type, t.quantity, t.reference, m.name AS medicine_name
        FROM medicine_transactions t
        JOIN medicines m ON m.id = t.medicine_id
        ORDER BY t.transaction_datetime DESC
        LIMIT 5
    ")->fetchAll();

    $stockAlerts = $pdo->query("
        SELECT * FROM (
            SELECT
                'low_stock' AS alert_type,
                m.name AS title,
                CONCAT('On hand: ', COALESCE(SUM(t.quantity), 0), ' | Reorder level: ', COALESCE(m.reorder_level, 0)) AS details,
                NULL AS alert_date,
                COALESCE(SUM(t.quantity), 0) AS sort_value
            FROM medicines m
            LEFT JOIN medicine_transactions t ON t.medicine_id = m.id
            GROUP BY m.id, m.name, m.reorder_level
            HAVING COALESCE(SUM(t.quantity), 0) <= COALESCE(m.reorder_level, 0)

            UNION ALL

            SELECT
                'expiring' AS alert_type,
                CONCAT(m.name, ' / Batch ', b.batch_no) AS title,
                CONCAT('Expires soon | On hand: ', COALESCE(SUM(t.quantity), 0)) AS details,
                b.expiry_date AS alert_date,
                COALESCE(SUM(t.quantity), 0) AS sort_value
            FROM medicine_batches b
            JOIN medicines m ON m.id = b.medicine_id
            LEFT JOIN medicine_transactions t ON t.batch_id = b.id
            WHERE b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            GROUP BY b.id, m.name, b.batch_no, b.expiry_date
            HAVING COALESCE(SUM(t.quantity), 0) > 0
        ) alerts
        ORDER BY
            CASE WHEN alert_type = 'low_stock' THEN 0 ELSE 1 END,
            COALESCE(alert_date, '9999-12-31') ASC,
            sort_value ASC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {
    // Keep the module page usable even if summary queries fail.
}
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Supply visibility</div>
      <div class="text-2xl font-semibold">Medicine Inventory Module</div>
      <p class="text-sm text-slate-500 mt-1">Monitor medicines, batches, and stock movement in one place.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Stock Health</span>
      <span class="app-chip">Batch Tracking</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/medicines/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M7 3h10v6H7z"></path>
          <path d="M5 9h14v12H5z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Inventory</div>
        <div class="text-lg font-semibold">Medicines</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Define medicine profiles and base stock info.</p>
  </a>
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
        <div class="text-sm text-slate-500">Inventory</div>
        <div class="text-lg font-semibold">Batches</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Track batch numbers, expiry, and availability.</p>
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
        <div class="text-sm text-slate-500">Inventory</div>
        <div class="text-lg font-semibold">Transactions & Stock</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Log incoming/outgoing movements and balances.</p>
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Medicines</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['medicines'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Registered medicine profiles</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Batches</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['batches'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Tracked medicine batches</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Low Stock</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['low_stock'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Medicines needing attention</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Movements</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['movements'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Logged stock transactions</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Recent Activity</div>
    <div class="text-lg font-semibold">Latest Stock Movement</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($recentTransactions)): ?>
        <div class="text-sm text-slate-500">No recent inventory transactions.</div>
      <?php else: ?>
        <?php foreach ($recentTransactions as $transaction): ?>
          <?php $qty = (int)$transaction['quantity']; ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($transaction['medicine_name']) ?></div>
                <div class="text-sm text-slate-500 mt-1"><?= h(ucfirst($transaction['transaction_type'])) ?><?= $transaction['reference'] ? ' • ' . h($transaction['reference']) : '' ?></div>
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
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Summary</div>
    <div class="text-lg font-semibold">Stock Attention Snapshot</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($stockAlerts)): ?>
        <div class="text-sm text-slate-500">No low-stock or expiring batch alerts right now.</div>
      <?php else: ?>
        <?php foreach ($stockAlerts as $alert): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($alert['title']) ?></div>
                <div class="text-sm text-slate-500 mt-1"><?= h($alert['details']) ?></div>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $alert['alert_type'] === 'low_stock' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700' ?>">
                <?= h($alert['alert_type'] === 'low_stock' ? 'Low Stock' : 'Expiring') ?>
              </span>
            </div>
            <?php if (!empty($alert['alert_date'])): ?>
              <div class="text-xs text-slate-400 mt-2">Date: <?= h($alert['alert_date']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
