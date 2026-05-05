<?php
$pageTitle = 'Vaccines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$doseFilter = $_GET['dose_group'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $doseFilter !== '') {
    $clauses = [];
    if ($q !== '') {
        $clauses[] = "(name LIKE ? OR code LIKE ?)";
        $like = '%' . $q . '%';
        $params = [$like, $like];
    }
    if ($doseFilter === 'single') {
        $clauses[] = "COALESCE(doses_required, 0) <= 1";
    } elseif ($doseFilter === 'multi') {
        $clauses[] = "COALESCE(doses_required, 0) > 1";
    }
    $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM vaccines $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT * FROM vaccines $where ORDER BY id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Vaccines</div>
  <button type="button" id="vaccineModalOpenNew" data-embed-url="/HealthLogs/public/immunization/vaccines/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Vaccine</button>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search vaccine name or code" />
  <select name="dose_group" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All dose groups</option>
    <option value="single" <?= $doseFilter === 'single' ? 'selected' : '' ?>>Single dose</option>
    <option value="multi" <?= $doseFilter === 'multi' ? 'selected' : '' ?>>Multiple doses</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/immunization/vaccines/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Code</th><th class="text-left px-4 py-2">Doses</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="4">No vaccines found.</td></tr><?php else: ?>
        <?php foreach ($rows as $v): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($v['name']) ?></td>
            <td class="px-4 py-2"><?= h($v['code']) ?></td>
            <td class="px-4 py-2"><?= h($v['doses_required']) ?></td>
            <td class="px-4 py-2"><button type="button" class="vaccine-modal-edit text-blue-600" data-embed-url="/HealthLogs/public/immunization/vaccines/form_embed.php?id=<?= (int)$v['id'] ?>">Edit</button><form method="post" action="/HealthLogs/public/immunization/vaccines/delete.php" class="inline" data-confirm="Delete this vaccine?" data-confirm-title="Delete vaccine" data-confirm-cta="Yes, delete"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>" /><button class="text-red-600 ml-2">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<div id="vaccineFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="vaccineFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Vaccine form</div>
        <button type="button" id="vaccineFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="vaccineFormModalFrame" class="w-full min-h-[70vh] border-0 flex-1" title="Vaccine form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('vaccineFormModal');
  var frame = document.getElementById('vaccineFormModalFrame');
  var backdrop = document.getElementById('vaccineFormModalBackdrop');
  var closeBtn = document.getElementById('vaccineFormModalClose');
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
  var newBtn = document.getElementById('vaccineModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.vaccine-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); });
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
