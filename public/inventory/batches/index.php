<?php
$pageTitle = 'Medicine Batches & Expiry Monitoring';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$availabilityFilter = $_GET['availability'] ?? '';
$expiryFilter = trim($_GET['expiry_status'] ?? '');

$whereClauses = [];
$params = [];

if ($q !== '') {
    $whereClauses[] = "(m.name LIKE ? OR b.batch_no LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($expiryFilter === 'expired') {
    $whereClauses[] = "b.expiry_date < CURDATE()";
} elseif ($expiryFilter === 'near_expiry') {
    $whereClauses[] = "b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
} elseif ($expiryFilter === 'valid') {
    $whereClauses[] = "b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
}

$where = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$havingSql = '';
if ($availabilityFilter === 'in_stock') {
    $havingSql = ' HAVING on_hand > 0';
} elseif ($availabilityFilter === 'empty') {
    $havingSql = ' HAVING on_hand <= 0';
}

// KPI Expiry Stats
$kpiStats = [
    'total' => 0,
    'valid' => 0,
    'near_expiry' => 0,
    'expired' => 0,
    'expired_stock' => 0,
    'near_expiry_stock' => 0
];

try {
    $kpiSql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 ELSE 0 END) AS valid_count,
                SUM(CASE WHEN b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 ELSE 0 END) AS near_expiry_count,
                SUM(CASE WHEN b.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_count
               FROM medicine_batches b";
    $kpiRow = $pdo->query($kpiSql)->fetch(PDO::FETCH_ASSOC);
    if ($kpiRow) {
        $kpiStats['total'] = (int)$kpiRow['total'];
        $kpiStats['valid'] = (int)$kpiRow['valid_count'];
        $kpiStats['near_expiry'] = (int)$kpiRow['near_expiry_count'];
        $kpiStats['expired'] = (int)$kpiRow['expired_count'];
    }

    // Near expiry with stock
    $kpiStats['near_expiry_stock'] = (int)$pdo->query("
        SELECT COALESCE(SUM(t.quantity), 0)
        FROM medicine_batches b
        JOIN medicine_transactions t ON t.batch_id = b.id
        WHERE b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ")->fetchColumn();

    // Expired with stock
    $kpiStats['expired_stock'] = (int)$pdo->query("
        SELECT COALESCE(SUM(t.quantity), 0)
        FROM medicine_batches b
        JOIN medicine_transactions t ON t.batch_id = b.id
        WHERE b.expiry_date < CURDATE()
    ")->fetchColumn();
} catch (Throwable $e) {}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM (
    SELECT b.id, COALESCE(SUM(t.quantity), 0) AS on_hand
    FROM medicine_batches b
    JOIN medicines m ON m.id = b.medicine_id
    LEFT JOIN medicine_transactions t ON t.batch_id = b.id
    $where
    GROUP BY b.id
    $havingSql
) filtered_batches");
$countStmt->execute($params);
$totalBatches = (int)$countStmt->fetchColumn();

$paginator = paginate($totalBatches, 20);

$querySql = "SELECT b.*, m.name AS medicine_name, m.unit,
                    COALESCE(SUM(t.quantity), 0) AS on_hand,
                    DATEDIFF(b.expiry_date, CURDATE()) AS days_remaining
             FROM medicine_batches b
             JOIN medicines m ON m.id = b.medicine_id
             LEFT JOIN medicine_transactions t ON t.batch_id = b.id
             $where
             GROUP BY b.id
             $havingSql
             ORDER BY b.expiry_date ASC, b.id DESC " . $paginator->getLimitSql();
$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php display_flash_messages(); ?>

<!-- Expiry Monitoring Notice Banners -->
<?php if ($kpiStats['expired_stock'] > 0): ?>
  <div class="mb-3 bg-rose-50/70 border border-rose-200 rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-start gap-3 min-w-0">
      <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
        <i class="fas fa-ban text-xs"></i>
      </div>
      <div class="min-w-0">
        <div class="text-sm font-bold text-rose-900"><?= number_format($kpiStats['expired_stock']) ?> expired units still in stock across batches</div>
        <p class="text-xs text-rose-700 mt-0.5">Blocked from patient issuance. Write off expired stock to quarantine and clear active expiry alerts.</p>
      </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <a href="?expiry_status=expired&availability=in_stock" class="px-3 py-1.5 rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 text-xs font-semibold transition">
        Review Expired Stock
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <form method="post" action="/HealthLogs/public/inventory/batches/write_off_expired.php" class="inline" data-confirm="Quarantine and write off all <?= number_format($kpiStats['expired_stock']) ?> expired units from physical inventory? Every batch disposal will be recorded in the Medicine Audit Trail." data-confirm-title="Write Off All Expired Stock" data-confirm-cta="Yes, write off all">
          <input type="hidden" name="write_off_all" value="1" />
          <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-xs">
            <i class="fas fa-trash-can mr-1 text-[10px]"></i> Write Off All Expired
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($kpiStats['near_expiry'] > 0): ?>
  <div class="mb-3 bg-slate-50 border border-slate-200 rounded p-3 flex items-start gap-3">
    <div class="text-slate-500 mt-0.5 shrink-0">
      <i class="fas fa-hourglass-end text-sm"></i>
    </div>
    <div class="flex-1 min-w-0">
      <div class="text-sm font-semibold text-slate-700"><?= number_format($kpiStats['near_expiry']) ?> batches expiring soon</div>
      <p class="text-xs text-slate-600 mt-0.5"><?= number_format($kpiStats['near_expiry_stock']) ?> units within 60 days</p>
    </div>
    <a href="?expiry_status=near_expiry" class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-medium transition shrink-0">
      View
    </a>
  </div>
<?php endif; ?>

<!-- Top Header -->
<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Batch Lifecycle Management</div>
      <div class="text-2xl font-semibold">Medicine Batches</div>
      <p class="text-sm text-slate-500 mt-1">Monitor batch numbers, expiry dates, and quarantine status for expired stock.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="/HealthLogs/public/inventory/audit_trail.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-clipboard-check"></i> Audit Trail
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="batchModalOpenNew" data-embed-url="/HealthLogs/public/inventory/batches/form_embed.php" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded text-xs font-semibold transition shadow-xs">
          <i class="fas fa-plus mr-1"></i> New Batch
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- KPI Pills -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100 text-xs">
    <a href="/HealthLogs/public/inventory/batches/index.php" class="p-3 rounded border <?= empty($expiryFilter) ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['total']) ?></div>
      <div class="text-[11px] <?= empty($expiryFilter) ? 'text-slate-300' : 'text-slate-500' ?>">Total Batches</div>
    </a>
    <a href="?expiry_status=valid" class="p-3 rounded border <?= $expiryFilter === 'valid' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['valid']) ?></div>
      <div class="text-[11px] <?= $expiryFilter === 'valid' ? 'text-slate-300' : 'text-slate-500' ?>">Valid (>60d)</div>
    </a>
    <a href="?expiry_status=near_expiry" class="p-3 rounded border <?= $expiryFilter === 'near_expiry' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['near_expiry']) ?></div>
      <div class="text-[11px] <?= $expiryFilter === 'near_expiry' ? 'text-slate-300' : 'text-slate-500' ?>">Near Expiry (≤60d)</div>
    </a>
    <a href="?expiry_status=expired" class="p-3 rounded border <?= $expiryFilter === 'expired' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['expired']) ?></div>
      <div class="text-[11px] <?= $expiryFilter === 'expired' ? 'text-slate-300' : 'text-slate-500' ?>">Expired (Barred)</div>
    </a>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3 mt-6">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
      <i class="fas fa-search text-xs"></i>
    </span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded text-sm" placeholder="Search medicine name or batch number" />
  </div>

  <select name="expiry_status" class="w-full md:w-52 border border-slate-200 rounded px-3 py-2 text-sm bg-white">
    <option value="">All Expiry Statuses</option>
    <option value="valid" <?= $expiryFilter === 'valid' ? 'selected' : '' ?>>Valid Only</option>
    <option value="near_expiry" <?= $expiryFilter === 'near_expiry' ? 'selected' : '' ?>>Near Expiry (≤ 60 days)</option>
    <option value="expired" <?= $expiryFilter === 'expired' ? 'selected' : '' ?>>Expired (Disallowed)</option>
  </select>

  <select name="availability" class="w-full md:w-44 border border-slate-200 rounded px-3 py-2 text-sm bg-white">
    <option value="">All stock levels</option>
    <option value="in_stock" <?= $availabilityFilter === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
    <option value="empty" <?= $availabilityFilter === 'empty' ? 'selected' : '' ?>>Depleted (0)</option>
  </select>

  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-slate-800 transition" type="submit">
      Filter
    </button>
    <a class="px-4 py-2 rounded border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition" href="/HealthLogs/public/inventory/batches/index.php">
      Clear
    </a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded shadow overflow-hidden mt-6">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3 px-4">Medicine</th>
          <th class="py-3 px-4">Batch No</th>
          <th class="py-3 px-4">Expiry Date</th>
          <th class="py-3 px-4">Classification</th>
          <th class="py-3 px-4">Received</th>
          <th class="py-3 px-4">On Hand</th>
          <th class="py-3 px-4">Issuance Status</th>
          <th class="py-3 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php if (empty($rows)): ?>
          <tr>
            <td class="px-4 py-8 text-center text-slate-500" colspan="8">
              No batches found matching the selected filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $b): ?>
            <?php
              $days = (int)$b['days_remaining'];
              $classification = ActivityLogger::classifyExpiry($b['expiry_date']);
              $onHand = (int)$b['on_hand'];
            ?>
            <tr class="hover:bg-slate-50 transition">
              <!-- Medicine Name -->
              <td class="px-4 py-3 font-semibold text-slate-900">
                <?= h($b['medicine_name']) ?>
              </td>

              <!-- Batch No -->
              <td class="px-4 py-3">
                <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-semibold">
                  <?= h($b['batch_no']) ?>
                </span>
              </td>

              <!-- Expiry Date -->
              <td class="px-4 py-3 whitespace-nowrap font-medium text-slate-800">
                <?= date('M d, Y', strtotime($b['expiry_date'])) ?>
              </td>

              <!-- Classification Badge -->
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($classification['key'] === 'expired'): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                    <i class="fas fa-times-circle text-[10px]"></i>
                    <span>Expired (<?= abs($days) ?>d ago)</span>
                  </span>
                <?php elseif ($classification['key'] === 'near_expiry'): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fas fa-exclamation-triangle text-[10px]"></i>
                    <span>Near Expiry (<?= $days ?>d left)</span>
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                    <i class="fas fa-check-circle text-[10px]"></i>
                    <span>Valid (<?= $days ?>d left)</span>
                  </span>
                <?php endif; ?>
              </td>

              <!-- Received -->
              <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                <?= number_format($b['quantity_received']) ?>
                <span class="text-[10px] text-slate-400 block"><?= date('M d, Y', strtotime($b['received_date'])) ?></span>
              </td>

              <!-- On Hand -->
              <td class="px-4 py-3 font-semibold whitespace-nowrap <?= $onHand <= 0 ? 'text-slate-400' : ($classification['is_expired'] ? 'text-rose-600 line-through' : 'text-slate-800') ?>">
                <?= number_format($onHand) ?> <?= h($b['unit'] ?? '') ?>
              </td>

              <!-- Issuance Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($classification['is_expired'] && $onHand <= 0): ?>
                  <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200" title="Expired stock has been written off or depleted">
                    <i class="fas fa-check-double text-[10px] text-slate-500"></i> Written Off (0)
                  </span>
                <?php elseif ($classification['is_expired']): ?>
                  <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200" title="Expired stock is blocked from issuance">
                    <i class="fas fa-lock text-[10px]"></i> Barred
                  </span>
                <?php elseif ($onHand <= 0): ?>
                  <span class="text-slate-400 text-xs">Depleted</span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                    <i class="fas fa-unlock text-[10px]"></i> Available
                  </span>
                <?php endif; ?>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <?php if ($classification['is_expired'] && $onHand > 0): ?>
                    <form method="post" action="/HealthLogs/public/inventory/batches/write_off_expired.php" class="inline" data-confirm="Safely write off and remove expired units from physical stock?" data-confirm-title="Write Off Expired Stock" data-confirm-cta="Yes, write off">
                      <input type="hidden" name="batch_id" value="<?= (int)$b['id'] ?>" />
                      <button type="submit" class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold mr-1" title="Quarantine and write off expired stock">
                        <i class="fas fa-trash-can mr-1 text-[10px]"></i> Write Off
                      </button>
                    </form>
                  <?php endif; ?>
                  <button type="button" class="batch-modal-edit px-2 py-1 rounded border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold mr-1" data-embed-url="/HealthLogs/public/inventory/batches/form_embed.php?id=<?= (int)$b['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/inventory/batches/delete.php" class="inline" data-confirm="Delete this batch? All opening transactions will be removed." data-confirm-title="Delete batch" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>" />
                    <button class="px-2 py-1 rounded border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold">Delete</button>
                  </form>
                <?php else: ?>
                  <span class="text-xs text-slate-400">Read-Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $paginator->render() ?>

<div id="batchFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="batchFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-5xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Batch form</div>
        <button type="button" id="batchFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="batchFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Batch form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('batchFormModal');
  var frame = document.getElementById('batchFormModalFrame');
  var backdrop = document.getElementById('batchFormModalBackdrop');
  var closeBtn = document.getElementById('batchFormModalClose');
  function openModal(url) {
    if (!modal || !frame || !url) return;
    frame.src = url;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    if (closeBtn) closeBtn.focus();
  }
  function closeModal() {
    if (!modal || !frame) return;
    frame.src = 'about:blank';
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }
  var newBtn = document.getElementById('batchModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.batch-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); });
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
})();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
