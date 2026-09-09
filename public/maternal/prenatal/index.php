<?php
$pageTitle = 'Prenatal Visits';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$periodFilter = $_GET['period'] ?? '';
$stageFilter = $_GET['stage'] ?? '';

$where = '';
$params = [];
$clauses = [];

if ($q !== '') {
    $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like];
}
if ($periodFilter === '30') {
    $clauses[] = "v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($periodFilter === '90') {
    $clauses[] = "v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
}
if ($stageFilter === 'sensitive') {
    $clauses[] = "v.gestational_age_weeks BETWEEN 24 AND 31";
} elseif ($stageFilter === 'high_bp') {
    $clauses[] = "(v.bp_systolic >= 140 OR v.bp_diastolic >= 90)";
}

if (!empty($clauses)) {
    $where = "WHERE " . implode(' AND ', $clauses);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM prenatal_visits v JOIN pregnancies pr ON pr.id = v.pregnancy_id JOIN patients p ON p.id = pr.patient_id $where");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$paginator = paginate($totalCount, 15);

$stmt = $pdo->prepare("
    SELECT v.*, p.first_name, p.last_name, p.contact_no, p.barangay,
           ROUND((COALESCE(v.gestational_age_weeks, 0) * 7) / 30.4375, 1) AS ga_months
    FROM prenatal_visits v 
    JOIN pregnancies pr ON pr.id = v.pregnancy_id 
    JOIN patients p ON p.id = pr.patient_id 
    $where 
    ORDER BY v.visit_datetime DESC " . $paginator->getLimitSql()
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Sensitive 6-7 months visits count
$sensitiveVisitsCount = (int)$pdo->query("
    SELECT COUNT(*) FROM prenatal_visits 
    WHERE gestational_age_weeks BETWEEN 24 AND 31
")->fetchColumn();
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded-xl shadow mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-medium">Clinical Antenatal Care</div>
      <div class="text-2xl font-semibold text-slate-900">Prenatal Checkup Visits</div>
      <p class="text-sm text-slate-500 mt-1">Track vitals, maternal progression, and highlight sensitive 6–7 months visits.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <?php if ($sensitiveVisitsCount > 0): ?>
        <a href="/HealthLogs/public/maternal/prenatal/index.php?stage=sensitive" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 hover:bg-rose-200 transition shadow-2xs">
          <i class="fas fa-heartbeat text-rose-600 animate-pulse"></i>
          <span><?= $sensitiveVisitsCount ?> Visits in 6–7 Mos Window</span>
        </a>
      <?php endif; ?>
      <button type="button" id="prenatalModalOpenNew" data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium shadow hover:bg-slate-800 transition">
        <i class="fas fa-plus mr-1 text-xs"></i> New Visit
      </button>
    </div>
  </div>
</div>

<form method="get" class="bg-white rounded-xl shadow p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Search patient name..." />
  <select name="period" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All visit periods</option>
    <option value="30" <?= $periodFilter === '30' ? 'selected' : '' ?>>Last 30 days</option>
    <option value="90" <?= $periodFilter === '90' ? 'selected' : '' ?>>Last 90 days</option>
  </select>
  <select name="stage" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Visit Categories</option>
    <option value="sensitive" <?= $stageFilter === 'sensitive' ? 'selected' : '' ?>>⚠️ Sensitive (6–7 Months / 24–31 wks)</option>
    <option value="high_bp" <?= $stageFilter === 'high_bp' ? 'selected' : '' ?>>High BP (≥140/90 mmHg)</option>
  </select>
  <div class="flex gap-2">
    <button class="flex-1 bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition" type="submit">Filter</button>
    <a class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm text-center hover:bg-slate-50 transition" href="/HealthLogs/public/maternal/prenatal/index.php">Clear</a>
  </div>
</form>

<div class="mt-6 bg-white rounded-xl shadow overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
        <tr>
          <th class="text-left px-4 py-3 font-semibold">Patient</th>
          <th class="text-left px-4 py-3 font-semibold">Visit Date & Time</th>
          <th class="text-left px-4 py-3 font-semibold">Gestational Age</th>
          <th class="text-left px-4 py-3 font-semibold">Blood Pressure</th>
          <th class="text-left px-4 py-3 font-semibold">Weight</th>
          <th class="text-right px-4 py-3 font-semibold">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-6 text-center text-slate-500" colspan="6">No prenatal visits found matching current filter.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $ga = (int)($r['gestational_age_weeks'] ?? 0);
              $gaMonths = (float)($r['ga_months'] ?? 0);
              $isSensitive = ($ga >= 24 && $ga <= 31);
              $isHighBp = ((int)($r['bp_systolic'] ?? 0) >= 140 || (int)($r['bp_diastolic'] ?? 0) >= 90);

              $rowClass = "hover:bg-slate-50/80 transition";
              if ($isSensitive) {
                  $rowClass = "bg-rose-50/70 hover:bg-rose-50 transition border-l-4 border-l-rose-500";
              } elseif ($isHighBp) {
                  $rowClass = "bg-amber-50/40 hover:bg-amber-50 transition border-l-4 border-l-amber-400";
              }
            ?>
            <tr class="<?= $rowClass ?>">
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></div>
                <?php if ($r['barangay']): ?>
                  <div class="text-xs text-slate-500"><?= h($r['barangay']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-700 whitespace-nowrap font-mono text-xs">
                <?= date('M d, Y h:i A', strtotime($r['visit_datetime'])) ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($isSensitive): ?>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300 shadow-2xs">
                    <i class="fas fa-exclamation-triangle text-rose-600 animate-pulse"></i>
                    <span><?= $ga ?> wks (~<?= $gaMonths ?> mos) • <strong>6–7 Mos Sensitive</strong></span>
                  </span>
                <?php elseif ($ga > 31): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                    <?= $ga ?> wks • 3rd Trimester
                  </span>
                <?php elseif ($ga > 0): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    <?= $ga ?> wks (~<?= $gaMonths ?> mos)
                  </span>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($isHighBp): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                    <i class="fas fa-heartbeat text-rose-600"></i>
                    <?= h((string)$r['bp_systolic']) ?>/<?= h((string)$r['bp_diastolic']) ?> mmHg (High)
                  </span>
                <?php elseif ($r['bp_systolic'] && $r['bp_diastolic']): ?>
                  <span class="text-slate-800 font-mono text-xs">
                    <?= h((string)$r['bp_systolic']) ?>/<?= h((string)$r['bp_diastolic']) ?> mmHg
                  </span>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-slate-700 text-xs">
                <?= $r['weight_kg'] ? h((string)$r['weight_kg']) . ' kg' : '—' ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                <div class="inline-flex items-center gap-2">
                  <button type="button" class="prenatal-modal-edit text-blue-600 hover:text-blue-900 font-medium px-2 py-1 border border-blue-200 rounded hover:bg-blue-50" data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php?id=<?= (int)$r['id'] ?>">
                    Edit
                  </button>
                  <form method="post" action="/HealthLogs/public/maternal/prenatal/delete.php" class="inline" data-confirm="Delete this visit record?" data-confirm-title="Delete prenatal visit" data-confirm-cta="Yes, delete">
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
