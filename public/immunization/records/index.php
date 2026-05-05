<?php
$pageTitle = 'Immunization Records';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$vaccineFilter = (int)($_GET['vaccine_id'] ?? 0);
$where = '';
$params = [];
if ($q !== '' || $vaccineFilter > 0) {
    $clauses = [];
    if ($q !== '') {
        $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR v.name LIKE ?)";
        $like = '%' . $q . '%';
        $params = [$like, $like, $like];
    }
    if ($vaccineFilter > 0) {
        $clauses[] = "v.id = ?";
        $params[] = $vaccineFilter;
    }
    $where = "WHERE " . implode(' AND ', $clauses);
}
$vaccines = $pdo->query("SELECT id, name FROM vaccines ORDER BY name ASC")->fetchAll();
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM immunization_records r JOIN patients p ON p.id = r.patient_id JOIN vaccines v ON v.id = r.vaccine_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT r.*, p.first_name, p.last_name, v.name AS vaccine_name FROM immunization_records r JOIN patients p ON p.id = r.patient_id JOIN vaccines v ON v.id = r.vaccine_id $where ORDER BY r.administered_at DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Immunization Records</div>
  <button type="button" id="recordModalOpenNew" data-embed-url="/HealthLogs/public/immunization/records/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Record</button>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search patient or vaccine" />
  <select name="vaccine_id" class="w-full md:w-64 border rounded px-3 py-2">
    <option value="0">All vaccines</option>
    <?php foreach ($vaccines as $vaccine): ?>
      <option value="<?= (int)$vaccine['id'] ?>" <?= $vaccineFilter === (int)$vaccine['id'] ? 'selected' : '' ?>><?= h($vaccine['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button>
    <a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/immunization/records/index.php">Clear</a>
  </div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Patient</th><th class="text-left px-4 py-2">Vaccine</th><th class="text-left px-4 py-2">Dose</th><th class="text-left px-4 py-2">Date/Time</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="5">No records found.</td></tr><?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['vaccine_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['dose_no']) ?></td>
            <td class="px-4 py-2"><?= h($r['administered_at']) ?></td>
            <td class="px-4 py-2"><button type="button" class="record-modal-edit text-blue-600" data-embed-url="/HealthLogs/public/immunization/records/form_embed.php?id=<?= (int)$r['id'] ?>">Edit</button><form method="post" action="/HealthLogs/public/immunization/records/delete.php" class="inline" data-confirm="Delete this record?" data-confirm-title="Delete immunization record" data-confirm-cta="Yes, delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>" /><button class="text-red-600 ml-2">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<div id="recordFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="recordFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Immunization record form</div>
        <button type="button" id="recordFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="recordFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Immunization record form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('recordFormModal');
  var frame = document.getElementById('recordFormModalFrame');
  var backdrop = document.getElementById('recordFormModalBackdrop');
  var closeBtn = document.getElementById('recordFormModalClose');
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
  var newBtn = document.getElementById('recordModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.record-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); });
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
