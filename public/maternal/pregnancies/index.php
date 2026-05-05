<?php
$pageTitle = 'Pregnancies';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $statusFilter !== '') {
  $clauses = [];
  if ($q !== '') { $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)"; $like = '%' . $q . '%'; $params = [$like, $like]; }
  if (in_array($statusFilter, ['ongoing', 'delivered', 'terminated'], true)) { $clauses[] = "pr.status = ?"; $params[] = $statusFilter; }
  $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT pr.*, p.first_name, p.last_name FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id $where ORDER BY pr.id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Pregnancies</div>
  <button type="button" id="pregnancyModalOpenNew" data-embed-url="/HealthLogs/public/maternal/pregnancies/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Pregnancy</button>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search patient or status" />
  <select name="status" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All statuses</option>
    <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
    <option value="terminated" <?= $statusFilter === 'terminated' ? 'selected' : '' ?>>Terminated</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/maternal/pregnancies/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Patient</th><th class="text-left px-4 py-2">LMP</th><th class="text-left px-4 py-2">EDD</th><th class="text-left px-4 py-2">Status</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="5">No pregnancies found.</td></tr><?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['lmp_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['edd_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['status']) ?></td>
            <td class="px-4 py-2"><button type="button" class="pregnancy-modal-edit text-blue-600" data-embed-url="/HealthLogs/public/maternal/pregnancies/form_embed.php?id=<?= (int)$r['id'] ?>">Edit</button><form method="post" action="/HealthLogs/public/maternal/pregnancies/delete.php" class="inline" data-confirm="Delete this record?" data-confirm-title="Delete pregnancy" data-confirm-cta="Yes, delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>" /><button class="text-red-600 ml-2">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<div id="pregnancyFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="pregnancyFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Pregnancy form</div>
        <button type="button" id="pregnancyFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="pregnancyFormModalFrame" class="w-full min-h-[70vh] border-0 flex-1" title="Pregnancy form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('pregnancyFormModal');
  var frame = document.getElementById('pregnancyFormModalFrame');
  var backdrop = document.getElementById('pregnancyFormModalBackdrop');
  var closeBtn = document.getElementById('pregnancyFormModalClose');
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
  var newBtn = document.getElementById('pregnancyModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.pregnancy-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); });
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
