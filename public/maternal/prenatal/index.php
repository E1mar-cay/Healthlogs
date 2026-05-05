<?php
$pageTitle = 'Prenatal Visits';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$periodFilter = $_GET['period'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $periodFilter !== '') {
  $clauses = [];
  if ($q !== '') { $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)"; $like = '%' . $q . '%'; $params = [$like, $like]; }
  if ($periodFilter === '30') { $clauses[] = "v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; }
  elseif ($periodFilter === '90') { $clauses[] = "v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 90 DAY)"; }
  $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM prenatal_visits v JOIN pregnancies pr ON pr.id = v.pregnancy_id JOIN patients p ON p.id = pr.patient_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT v.*, p.first_name, p.last_name FROM prenatal_visits v JOIN pregnancies pr ON pr.id = v.pregnancy_id JOIN patients p ON p.id = pr.patient_id $where ORDER BY v.visit_datetime DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Prenatal Visits</div>
  <button type="button" id="prenatalModalOpenNew" data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Visit</button>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search patient name" />
  <select name="period" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All visits</option>
    <option value="30" <?= $periodFilter === '30' ? 'selected' : '' ?>>Last 30 days</option>
    <option value="90" <?= $periodFilter === '90' ? 'selected' : '' ?>>Last 90 days</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/maternal/prenatal/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Patient</th><th class="text-left px-4 py-2">Visit Date</th><th class="text-left px-4 py-2">GA Weeks</th><th class="text-left px-4 py-2">BP</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="5">No visits found.</td></tr><?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['visit_datetime']) ?></td>
            <td class="px-4 py-2"><?= h($r['gestational_age_weeks']) ?></td>
            <td class="px-4 py-2"><?= h($r['bp_systolic']) ?>/<?= h($r['bp_diastolic']) ?></td>
            <td class="px-4 py-2"><button type="button" class="prenatal-modal-edit text-blue-600" data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php?id=<?= (int)$r['id'] ?>">Edit</button><form method="post" action="/HealthLogs/public/maternal/prenatal/delete.php" class="inline" data-confirm="Delete this visit?" data-confirm-title="Delete prenatal visit" data-confirm-cta="Yes, delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>" /><button class="text-red-600 ml-2">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<div id="prenatalFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="prenatalFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Prenatal visit form</div>
        <button type="button" id="prenatalFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="prenatalFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Prenatal form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('prenatalFormModal');
  var frame = document.getElementById('prenatalFormModalFrame');
  var backdrop = document.getElementById('prenatalFormModalBackdrop');
  var closeBtn = document.getElementById('prenatalFormModalClose');
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
  var newBtn = document.getElementById('prenatalModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.prenatal-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); });
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
