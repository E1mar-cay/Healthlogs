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
$paginator = paginate((int)$countStmt->fetchColumn(), 20);
$stmt = $pdo->prepare("SELECT * FROM vaccines $where ORDER BY id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>

<!-- Page Header -->
<div class="bg-white p-5 rounded-xl border border-slate-200 mb-4">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-medium text-slate-500 uppercase tracking-wider"><i class="fas fa-vial mr-1.5 text-teal-700"></i>Immunization</div>
      <h1 class="text-xl font-semibold text-slate-900 mt-1">Vaccine catalog</h1>
      <p class="text-sm text-slate-500 mt-1">Manage vaccine names, codes, and required doses.</p>
    </div>
    <div class="flex items-center gap-2">
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="vaccineModalOpenNew" data-embed-url="/HealthLogs/public/immunization/vaccines/form_embed.php"
          class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
          <i class="fas fa-plus mr-1"></i> New Vaccine
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
<form method="get" class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col md:flex-row gap-3 mb-4">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fas fa-search text-xs"></i></span>
    <label class="sr-only" for="vaccineSearch">Search vaccines</label>
    <input id="vaccineSearch" name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/10" placeholder="Search vaccine name or code" />
  </div>
  <label class="sr-only" for="vaccineDoseGroup">Filter by dose group</label>
  <select id="vaccineDoseGroup" name="dose_group" class="w-full md:w-52 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/10">
    <option value="">All dose groups</option>
    <option value="single" <?= $doseFilter === 'single' ? 'selected' : '' ?>>Single dose</option>
    <option value="multi" <?= $doseFilter === 'multi' ? 'selected' : '' ?>>Multiple doses</option>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition" type="submit">Apply filters</button>
    <a class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-medium transition" href="/HealthLogs/public/immunization/vaccines/index.php">Clear</a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3.5 px-4">Vaccine Name</th>
          <th class="py-3.5 px-4">Code</th>
          <th class="py-3.5 px-4">Doses Required</th>
          <th class="py-3.5 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-8 text-center text-slate-500" colspan="4">No vaccines found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $v): ?>
            <tr class="hover:bg-slate-50/70 transition-colors">
              <td class="px-4 py-3 font-medium text-slate-900"><?= h($v['name']) ?></td>
              <td class="px-4 py-3">
                <span class="font-mono text-xs bg-slate-50 px-2 py-1 rounded text-slate-600"><?= h($v['code']) ?></span>
              </td>
              <td class="px-4 py-3 text-slate-700"><?= (int)$v['doses_required'] ?> dose<?= (int)$v['doses_required'] !== 1 ? 's' : '' ?></td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <button type="button" class="vaccine-modal-edit px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-medium mr-1"
                    data-embed-url="/HealthLogs/public/immunization/vaccines/form_embed.php?id=<?= (int)$v['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/immunization/vaccines/delete.php" class="inline"
                    data-confirm="Delete this vaccine?" data-confirm-title="Delete vaccine" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>" />
                    <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 text-xs font-medium">Delete</button>
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
<div id="vaccineFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="vaccineFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 bg-white">
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
