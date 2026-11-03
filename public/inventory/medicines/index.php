<?php
$pageTitle = 'Medicines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$stockFilter = $_GET['stock'] ?? '';
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE m.name LIKE ? OR m.generic_name LIKE ? OR m.unit LIKE ?";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$havingSql = '';
if ($stockFilter === 'low') { $havingSql = ' HAVING COALESCE(MAX(stk.valid_stock), 0) <= COALESCE(MAX(m.reorder_level), 0)'; }
elseif ($stockFilter === 'available') { $havingSql = ' HAVING COALESCE(MAX(stk.valid_stock), 0) > COALESCE(MAX(m.reorder_level), 0)'; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM (
    SELECT m.id, COALESCE(MAX(stk.valid_stock), 0) AS valid_stock
    FROM medicines m
    LEFT JOIN (
        SELECT mb.medicine_id, SUM(t.quantity) AS valid_stock
        FROM medicine_batches mb JOIN medicine_transactions t ON t.batch_id = mb.id
        WHERE mb.expiry_date >= CURDATE() GROUP BY mb.medicine_id
    ) stk ON stk.medicine_id = m.id
    $where GROUP BY m.id$havingSql
) fm");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 20);

$stmt = $pdo->prepare("SELECT m.*,
    COALESCE(MAX(stk.valid_stock), 0) AS valid_stock,
    COALESCE(MAX(exp_stk.expired_stock), 0) AS expired_stock
FROM medicines m
LEFT JOIN (
    SELECT mb.medicine_id, SUM(t.quantity) AS valid_stock
    FROM medicine_batches mb JOIN medicine_transactions t ON t.batch_id = mb.id
    WHERE mb.expiry_date >= CURDATE() GROUP BY mb.medicine_id
) stk ON stk.medicine_id = m.id
LEFT JOIN (
    SELECT mb.medicine_id, SUM(t.quantity) AS expired_stock
    FROM medicine_batches mb JOIN medicine_transactions t ON t.batch_id = mb.id
    WHERE mb.expiry_date < CURDATE() GROUP BY mb.medicine_id
) exp_stk ON exp_stk.medicine_id = m.id
$where GROUP BY m.id$havingSql ORDER BY m.name ASC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();

$lowStockCount = (int)$pdo->query("
    SELECT COUNT(*) FROM (
        SELECT m.id FROM medicines m
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(t.quantity) AS valid_stock
            FROM medicine_batches mb JOIN medicine_transactions t ON t.batch_id = mb.id
            WHERE mb.expiry_date >= CURDATE() GROUP BY mb.medicine_id
        ) stk ON stk.medicine_id = m.id
        GROUP BY m.id
        HAVING COALESCE(MAX(stk.valid_stock), 0) <= COALESCE(MAX(m.reorder_level), 0)
          AND COALESCE(MAX(m.reorder_level), 0) > 0
    ) ls
")->fetchColumn();
?>
<?php display_flash_messages(); ?>

<?php if ($lowStockCount > 0): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-amber-800">
  <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
    <i class="fas fa-triangle-exclamation text-sm"></i>
  </div>
  <div class="flex-1 min-w-0">
    <div class="font-bold text-sm">Low Stock Alert</div>
    <p class="text-xs text-amber-700 mt-0.5">
      <strong><?= number_format($lowStockCount) ?> medicine<?= $lowStockCount !== 1 ? 's' : '' ?></strong> are at or below their reorder level. Replenishment is recommended.
    </p>
  </div>
  <a href="?stock=low" class="px-3 py-1.5 rounded-xl bg-amber-600 text-white hover:bg-amber-700 text-xs font-semibold transition shrink-0">View Low Stock</a>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-semibold text-teal-600 uppercase tracking-widest"><i class="fas fa-pills mr-1"></i> Medicine Inventory</div>
      <div class="text-xl font-bold text-slate-900 mt-0.5">Medicines</div>
      <p class="text-xs text-slate-500 mt-0.5">Manage medicine catalog, reorder levels, and stock visibility.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="/HealthLogs/public/inventory/batches/index.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-boxes-stacked mr-1.5 text-teal-600"></i> Batches
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="medicineModalOpenNew" data-embed-url="/HealthLogs/public/inventory/medicines/form_embed.php"
          class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold transition shadow-xs">
          <i class="fas fa-plus mr-1"></i> New Medicine
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col md:flex-row gap-3 mb-5">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fas fa-search text-xs"></i></span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="Search medicine name, generic name, or unit" />
  </div>
  <select name="stock" class="w-full md:w-52 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All stock levels</option>
    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low / At Reorder Level</option>
    <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Above Reorder Level</option>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-800 transition" type="submit">Filter</button>
    <a class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition" href="/HealthLogs/public/inventory/medicines/index.php">Clear</a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-5">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3.5 px-4">Medicine Name</th>
          <th class="py-3.5 px-4">Generic Name</th>
          <th class="py-3.5 px-4">Unit</th>
          <th class="py-3.5 px-4">Available Stock</th>
          <th class="py-3.5 px-4">Reorder Level</th>
          <th class="py-3.5 px-4">Stock Status</th>
          <th class="py-3.5 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-8 text-center text-slate-500" colspan="7">No medicines found matching the selected filters.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $m): ?>
            <?php
              $validStock = (float)$m['valid_stock'];
              $reorderLevel = (float)($m['reorder_level'] ?? 0);
              $isLow = $reorderLevel > 0 && $validStock <= $reorderLevel;
            ?>
            <tr class="hover:bg-slate-50/60 transition">
              <td class="px-4 py-3 font-semibold text-slate-900"><?= h($m['name']) ?></td>
              <td class="px-4 py-3 text-slate-600"><?= h($m['generic_name'] ?: '—') ?></td>
              <td class="px-4 py-3 text-slate-600"><?= h($m['unit']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-semibold <?= $isLow ? 'text-rose-600' : 'text-slate-800' ?>"><?= number_format($validStock) ?></span>
                <?php if (!empty($m['expired_stock']) && $m['expired_stock'] > 0): ?>
                  <div class="text-[10px] text-rose-500 font-medium mt-1 flex items-center gap-1.5">
                    <i class="fas fa-ban text-[9px]"></i>
                    <span><?= number_format($m['expired_stock']) ?> expired</span>
                    <form method="post" action="/HealthLogs/public/inventory/medicines/remove_expired.php" class="inline" data-confirm="Remove all expired batches for this medicine?" data-confirm-title="Remove Expired" data-confirm-cta="Yes, remove">
                      <input type="hidden" name="medicine_id" value="<?= (int)$m['id'] ?>" />
                      <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold underline text-[9px]">Remove</button>
                    </form>
                  </div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600"><?= $reorderLevel > 0 ? number_format($reorderLevel) : '—' ?></td>
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($isLow): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                    <i class="fas fa-arrow-down text-[9px]"></i> Low Stock
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fas fa-check text-[9px]"></i> Adequate
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <button type="button" class="medicine-modal-edit px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold mr-1"
                    data-embed-url="/HealthLogs/public/inventory/medicines/form_embed.php?id=<?= (int)$m['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/inventory/medicines/delete.php" class="inline"
                    data-confirm="Delete this medicine?" data-confirm-title="Delete medicine" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
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

<div id="medicineFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="medicineFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Medicine form</div>
        <button type="button" id="medicineFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="medicineFormModalFrame" class="w-full min-h-[70vh] border-0 flex-1" title="Medicine form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('medicineFormModal');
  var frame = document.getElementById('medicineFormModalFrame');
  var backdrop = document.getElementById('medicineFormModalBackdrop');
  var closeBtn = document.getElementById('medicineFormModalClose');
  function openModal(url) { if (!modal || !frame || !url) return; frame.src = url; modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); if (closeBtn) closeBtn.focus(); }
  function closeModal() { if (!modal || !frame) return; frame.src = 'about:blank'; modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
  var newBtn = document.getElementById('medicineModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.medicine-modal-edit').forEach(function (btn) { btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); }); });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
