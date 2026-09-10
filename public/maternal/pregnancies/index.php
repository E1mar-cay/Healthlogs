<?php
$pageTitle = 'Pregnancies';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$stageFilter = $_GET['stage'] ?? '';

$where = '';
$params = [];
$clauses = [];

if ($q !== '') {
    $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.barangay LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
if (in_array($statusFilter, ['ongoing', 'delivered', 'terminated'], true)) {
    $clauses[] = "pr.status = ?";
    $params[] = $statusFilter;
}
if ($stageFilter === 'sensitive') {
    $clauses[] = "pr.status = 'ongoing' AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)";
} elseif ($stageFilter === 'late') {
    $clauses[] = "pr.status = 'ongoing' AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) > (31 * 7)";
} elseif ($stageFilter === 'early') {
    $clauses[] = "pr.status = 'ongoing' AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) < (24 * 7)";
}

if (!empty($clauses)) {
    $where = "WHERE " . implode(' AND ', $clauses);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id $where");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$paginator = paginate($totalCount, 15);

$stmt = $pdo->prepare("
    SELECT pr.*, p.first_name, p.last_name, p.contact_no, p.barangay,
           FLOOR(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 7) AS weeks_pregnant,
           ROUND(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 30.4375, 1) AS months_pregnant
    FROM pregnancies pr 
    JOIN patients p ON p.id = pr.patient_id 
    $where 
    ORDER BY pr.id DESC " . $paginator->getLimitSql()
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Sensitive count for quick banner badge
$sensitiveCount = (int)$pdo->query("
    SELECT COUNT(*) FROM pregnancies 
    WHERE status = 'ongoing' 
      AND TIMESTAMPDIFF(DAY, lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)
")->fetchColumn();
?>

<?php display_flash_messages(); ?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Pregnancies</div>
  <div class="flex items-center gap-2">
    <?php if ($sensitiveCount > 0): ?>
      <a href="/HealthLogs/public/maternal/pregnancies/index.php?status=ongoing&stage=sensitive" class="px-2.5 py-1.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 hover:bg-rose-200 transition">
        <?= $sensitiveCount ?> Sensitive (6–7 Mos)
      </a>
    <?php endif; ?>
    <button type="button" id="pregnancyModalOpenNew" data-embed-url="/HealthLogs/public/maternal/pregnancies/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded text-xs font-semibold hover:bg-slate-800 transition">New Pregnancy</button>
  </div>
</div>

<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2 text-sm" placeholder="Search patient name or barangay" />
  <select name="status" class="w-full md:w-44 border rounded px-3 py-2 text-sm">
    <option value="">All statuses</option>
    <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
    <option value="terminated" <?= $statusFilter === 'terminated' ? 'selected' : '' ?>>Terminated</option>
  </select>
  <select name="stage" class="w-full md:w-60 border rounded px-3 py-2 text-sm">
    <option value="">All Gestational Stages</option>
    <option value="sensitive" <?= $stageFilter === 'sensitive' ? 'selected' : '' ?>>Sensitive (6–7 Mos)</option>
    <option value="late" <?= $stageFilter === 'late' ? 'selected' : '' ?>>Near Term (>31 wks)</option>
    <option value="early" <?= $stageFilter === 'early' ? 'selected' : '' ?>>Early (&lt;24 wks)</option>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded text-xs font-semibold hover:bg-slate-800 transition" type="submit">Search</button>
    <a class="px-4 py-2 rounded border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition" href="/HealthLogs/public/maternal/pregnancies/index.php">Clear</a>
  </div>
</form>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Barangay</th>
        <th class="text-left px-4 py-2">LMP Date</th>
        <th class="text-left px-4 py-2">EDD Date</th>
        <th class="text-left px-4 py-2">Gestational Progress</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-6 text-center text-slate-500" colspan="7">No pregnancy records found matching current criteria.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $isOngoing = ($r['status'] === 'ongoing');
              $weeks = (int)($r['weeks_pregnant'] ?? 0);
              $months = (float)($r['months_pregnant'] ?? 0);
              
              // 6-7 months sensitive window (24 to 31 weeks)
              $isSensitive = ($isOngoing && $weeks >= 24 && $weeks <= 31);
              $isNearTerm = ($isOngoing && $weeks > 31);
              
              $rowClass = "hover:bg-slate-50/80 transition";
              if ($isSensitive) {
                  $rowClass = "bg-rose-50/70 hover:bg-rose-50 transition border-l-4 border-l-rose-500";
              } elseif ($isNearTerm) {
                  $rowClass = "bg-amber-50/40 hover:bg-amber-50 transition border-l-4 border-l-amber-400";
              }
            ?>
            <tr class="<?= $rowClass ?>">
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></div>
                <?php if ($r['contact_no']): ?>
                  <div class="text-xs text-slate-500 font-mono"><?= h($r['contact_no']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600"><?= h($r['barangay'] ?: '—') ?></td>
              <td class="px-4 py-3 text-slate-700 whitespace-nowrap"><?= h($r['lmp_date']) ?></td>
              <td class="px-4 py-3 text-slate-700 whitespace-nowrap font-medium"><?= h($r['edd_date']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($isSensitive): ?>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300 shadow-2xs">
                    <i class="fas fa-exclamation-triangle text-rose-600 animate-pulse"></i>
                    <span><?= $weeks ?> wks (~<?= $months ?> mos) • <strong>6–7 Mos Sensitive</strong></span>
                  </span>
                <?php elseif ($isNearTerm): ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fas fa-clock text-amber-600"></i>
                    <span><?= $weeks ?> wks (~<?= $months ?> mos) • 3rd Trimester</span>
                  </span>
                <?php elseif ($isOngoing): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    <?= $weeks ?> wks (~<?= $months ?> mos)
                  </span>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($r['status'] === 'ongoing'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Ongoing</span>
                <?php elseif ($r['status'] === 'delivered'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Delivered</span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">Terminated</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                <div class="inline-flex items-center gap-2">
                  <a href="/HealthLogs/public/maternal/prenatal/form.php?pregnancy_id=<?= (int)$r['id'] ?>" class="text-teal-700 hover:text-teal-900 font-semibold px-2 py-1 border border-teal-200 rounded hover:bg-teal-50" title="Record prenatal checkup">
                    <i class="fas fa-stethoscope mr-1"></i>Visit
                  </a>
                  <button type="button" class="pregnancy-modal-edit text-blue-600 hover:text-blue-900 font-medium px-2 py-1 border border-blue-200 rounded hover:bg-blue-50" data-embed-url="/HealthLogs/public/maternal/pregnancies/form_embed.php?id=<?= (int)$r['id'] ?>">
                    Edit
                  </button>
                  <form method="post" action="/HealthLogs/public/maternal/pregnancies/delete.php" class="inline" data-confirm="Delete this record?" data-confirm-title="Delete pregnancy" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                    <button class="text-red-600 hover:text-red-800 font-medium px-2 py-1 border border-red-200 rounded hover:bg-red-50">
                      Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4">
  <?= $paginator->render() ?>
</div>

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
