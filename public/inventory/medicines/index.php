<?php
$pageTitle = 'Medicines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$stockFilter = $_GET['stock'] ?? '';
$where = '';
$params = [];
if ($q !== '') { $where = "WHERE m.name LIKE ? OR m.generic_name LIKE ? OR m.unit LIKE ?"; $like = '%' . $q . '%'; $params = [$like, $like, $like]; }
$havingSql = '';
if ($stockFilter === 'low') { $havingSql = ' HAVING on_hand <= COALESCE(reorder_level, 0)'; }
elseif ($stockFilter === 'available') { $havingSql = ' HAVING on_hand > COALESCE(reorder_level, 0)'; }
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT m.id, m.reorder_level, COALESCE(SUM(mt.quantity), 0) AS on_hand FROM medicines m LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id $where GROUP BY m.id$havingSql) filtered_medicines");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT m.*, COALESCE(SUM(mt.quantity), 0) AS on_hand FROM medicines m LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id $where GROUP BY m.id$havingSql ORDER BY m.id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Medicines</div>
  <button type="button" id="medicineModalOpenNew" data-embed-url="/HealthLogs/public/inventory/medicines/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Medicine</button>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search medicine name, generic name, or unit" />
  <select name="stock" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All stock levels</option>
    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low stock</option>
    <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Above reorder level</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/inventory/medicines/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Generic</th><th class="text-left px-4 py-2">Unit</th><th class="text-left px-4 py-2">On Hand</th><th class="text-left px-4 py-2">Reorder Level</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="6">No medicines found.</td></tr><?php else: ?>
        <?php foreach ($rows as $m): ?>
          <?php $isLowStock = (float)$m['on_hand'] <= (float)($m['reorder_level'] ?? 0); ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($m['name']) ?></td>
            <td class="px-4 py-2"><?= h($m['generic_name']) ?></td>
            <td class="px-4 py-2"><?= h($m['unit']) ?></td>
            <td class="px-4 py-2 <?= $isLowStock ? 'text-red-600 font-semibold' : '' ?>"><?= h($m['on_hand']) ?></td>
            <td class="px-4 py-2"><?= h($m['reorder_level']) ?></td>
            <td class="px-4 py-2"><button type="button" class="medicine-modal-edit text-blue-600" data-embed-url="/HealthLogs/public/inventory/medicines/form_embed.php?id=<?= (int)$m['id'] ?>">Edit</button><form method="post" action="/HealthLogs/public/inventory/medicines/delete.php" class="inline" data-confirm="Delete this medicine?" data-confirm-title="Delete medicine" data-confirm-cta="Yes, delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>" /><button class="text-red-600 ml-2">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
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

  var newBtn = document.getElementById('medicineModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.medicine-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); });
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
