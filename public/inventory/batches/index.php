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

$paginator = paginate($totalBatches, 15);

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
  <div class="mb-4 bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-start gap-3 text-rose-800">
    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
      <i class="fas fa-ban text-sm"></i>
    </div>
    <div class="flex-1 min-w-0">
      <div class="font-bold text-sm">Expired Medicine Batches Detected</div>
      <p class="text-xs text-rose-700 mt-0.5">
        There are <strong><?= number_format($kpiStats['expired']) ?> expired batches</strong> holding <strong><?= number_format($kpiStats['expired_stock']) ?> units</strong> of medicine.
        Expired medicines are strictly <strong>prevented from being issued/dispensed</strong> and <strong>excluded from forecasting calculations</strong>.
      </p>
    </div>
    <a href="?expiry_status=expired" class="px-3 py-1.5 rounded-xl bg-rose-600 text-white hover:bg-rose-700 text-xs font-semibold transition shrink-0">
      View Expired
    </a>
  </div>
<?php endif; ?>

<?php if ($kpiStats['near_expiry'] > 0): ?>
  <div class="mb-5 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-amber-800">
    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
      <i class="fas fa-clock text-sm"></i>
    </div>
    <div class="flex-1 min-w-0">
      <div class="font-bold text-sm">Near Expiry Warning (Approaching within 60 Days)</div>
      <p class="text-xs text-amber-700 mt-0.5">
        <strong><?= number_format($kpiStats['near_expiry']) ?> batches</strong> (<?= number_format($kpiStats['near_expiry_stock']) ?> units on hand) are nearing expiration.
        Prioritize dispensing these batches following the First-Expired, First-Out (FEFO) guideline.
      </p>
    </div>
    <a href="?expiry_status=near_expiry" class="px-3 py-1.5 rounded-xl bg-amber-600 text-white hover:bg-amber-700 text-xs font-semibold transition shrink-0">
      View Near Expiry
    </a>
  </div>
<?php endif; ?>

<!-- Top Header -->
<div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-semibold text-teal-600 uppercase tracking-widest">
        <i class="fas fa-hourglass-half mr-1"></i> Automated Batch Expiration Monitoring
      </div>
      <div class="text-xl font-bold text-slate-900 mt-0.5">Medicine Batches</div>
      <p class="text-xs text-slate-500 mt-0.5">Classifying batch lifecycles as Valid, Near Expiry, or Expired with issuance controls.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="/HealthLogs/public/inventory/audit_trail.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-clipboard-check mr-1.5 text-teal-600"></i> Audit Trail
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="batchModalOpenNew" data-embed-url="/HealthLogs/public/inventory/batches/form_embed.php" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold transition shadow-xs">
          <i class="fas fa-plus mr-1"></i> New Batch
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- KPI Pills -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100 text-xs">
    <a href="/HealthLogs/public/inventory/batches/index.php" class="p-3 rounded-xl border <?= empty($expiryFilter) ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['total']) ?></div>
      <div class="text-[11px] <?= empty($expiryFilter) ? 'text-slate-300' : 'text-slate-500' ?>">Total Batches</div>
    </a>
    <a href="?expiry_status=valid" class="p-3 rounded-xl border <?= $expiryFilter === 'valid' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100 text-emerald-800' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['valid']) ?></div>
      <div class="text-[11px] <?= $expiryFilter === 'valid' ? 'text-emerald-100' : 'text-emerald-600' ?>">Valid (>60d)</div>
    </a>
    <a href="?expiry_status=near_expiry" class="p-3 rounded-xl border <?= $expiryFilter === 'near_expiry' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50/60 border-amber-200 hover:bg-amber-100 text-amber-800' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['near_expiry']) ?></div>
      <div class="text-[11px] <?= $expiryFilter === 'near_expiry' ? 'text-amber-100' : 'text-amber-600' ?>">Near Expiry (≤60d)</div>
    </a>
    <a href="?expiry_status=expired" class="p-3 rounded-xl border <?= $expiryFilter === 'expired' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50/60 border-rose-200 hover:bg-rose-100 text-rose-800' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format($kpiStats['expired']) ?></div>
      <div class="text-[11px] <?= $expiryFilter === 'expired' ? 'text-rose-100' : 'text-rose-600' ?>">Expired (Barred)</div>
    </a>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col md:flex-row gap-3 mb-5">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
      <i class="fas fa-search text-xs"></i>
    </span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="Search medicine name or batch number" />
  </div>

  <select name="expiry_status" class="w-full md:w-52 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All Expiry Statuses</option>
    <option value="valid" <?= $expiryFilter === 'valid' ? 'selected' : '' ?>>Valid Only</option>
    <option value="near_expiry" <?= $expiryFilter === 'near_expiry' ? 'selected' : '' ?>>Near Expiry (≤ 60 days)</option>
    <option value="expired" <?= $expiryFilter === 'expired' ? 'selected' : '' ?>>Expired (Disallowed)</option>
  </select>

  <select name="availability" class="w-full md:w-44 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All stock levels</option>
    <option value="in_stock" <?= $availabilityFilter === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
    <option value="empty" <?= $availabilityFilter === 'empty' ? 'selected' : '' ?>>Depleted (0)</option>
  </select>

  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-800 transition" type="submit">
      Filter
    </button>
    <a class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition" href="/HealthLogs/public/inventory/batches/index.php">
      Clear
    </a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-5">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3.5 px-4">Medicine</th>
          <th class="py-3.5 px-4">Batch No</th>
          <th class="py-3.5 px-4">Expiry Date</th>
          <th class="py-3.5 px-4">Classification</th>
          <th class="py-3.5 px-4">Received</th>
          <th class="py-3.5 px-4">On Hand</th>
          <th class="py-3.5 px-4">Issuance Status</th>
          <th class="py-3.5 px-4 text-right">Actions</th>
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
            <tr class="hover:bg-slate-50/60 transition">
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
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                    <i class="fas fa-times-circle text-[10px]"></i>
                    <span>Expired (<?= abs($days) ?>d ago)</span>
                  </span>
                <?php elseif ($classification['key'] === 'near_expiry'): ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fas fa-exclamation-triangle text-[10px]"></i>
                    <span>Near Expiry (<?= $days ?>d left)</span>
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
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
                <?php if ($classification['is_expired']): ?>
                  <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200" title="Expired stock is blocked from issuance">
                    <i class="fas fa-lock text-[10px]"></i> Barred from Issuance
                  </span>
                <?php elseif ($onHand <= 0): ?>
                  <span class="text-slate-400 text-xs">Depleted</span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    <i class="fas fa-unlock text-[10px]"></i> Available to Issue
                  </span>
                <?php endif; ?>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <button type="button" class="batch-modal-edit px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold mr-1" data-embed-url="/HealthLogs/public/inventory/batches/form_embed.php?id=<?= (int)$b['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/inventory/batches/delete.php" class="inline" data-confirm="Delete this batch? All opening transactions will be removed." data-confirm-title="Delete batch" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>" />
                    <button class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold">Delete</button>
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
