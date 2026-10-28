<?php
$pageTitle = 'Pregnancies';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
// Tab-based status: ongoing | delivered | miscarriage
$activeTab = $_GET['tab'] ?? 'ongoing';
if (!in_array($activeTab, ['ongoing', 'delivered', 'miscarriage'], true)) {
    $activeTab = 'ongoing';
}
$statusFilter = $activeTab;
$stageFilter = $_GET['stage'] ?? '';

$where = '';
$params = [];
$clauses = [];

if ($q !== '') {
    $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.barangay LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
if ($activeTab === 'miscarriage') {
    $clauses[] = "pr.status IN ('miscarriage', 'terminated')";
} else {
    $clauses[] = "pr.status = ?";
    $params[] = $activeTab;
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

// Tab counts
$tabCounts = [];
foreach (['ongoing' => "status = 'ongoing'", 'delivered' => "status = 'delivered'", 'miscarriage' => "status IN ('miscarriage','terminated')"] as $tab => $cond) {
    $tabCounts[$tab] = (int)$pdo->query("SELECT COUNT(*) FROM pregnancies WHERE $cond")->fetchColumn();
}
$maternalSummary = [
    'total' => array_sum($tabCounts),
    'prenatal_visits' => (int)$pdo->query("SELECT COUNT(*) FROM prenatal_visits")->fetchColumn(),
    'postnatal_visits' => (int)$pdo->query("SELECT COUNT(*) FROM postnatal_visits")->fetchColumn(),
];
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-5 rounded-xl border border-slate-200 mb-4">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Maternal Health</div>
      <div class="text-xl font-semibold text-slate-900 mt-0.5">Pregnancy Records</div>
      <p class="text-sm text-slate-500 mt-1">Patient information, pregnancy status, and prenatal/postnatal visit records.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <?php if ($sensitiveCount > 0): ?>
        <a href="/HealthLogs/public/maternal/pregnancies/index.php?tab=ongoing&stage=sensitive" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition">
          <span><?= $sensitiveCount ?> Sensitive (6–7 Mos)</span>
        </a>
      <?php endif; ?>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="pregnancyModalOpenNew" data-embed-url="/HealthLogs/public/maternal/pregnancies/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition">
          New Pregnancy
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-50 text-slate-600 border border-slate-200">
          Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabs -->
  <div class="flex items-center gap-1 mt-4 pt-4 border-t border-slate-100">
    <?php
    $tabs = [
        'ongoing'    => ['label' => 'Ongoing'],
        'delivered'  => ['label' => 'Delivered'],
        'miscarriage'=> ['label' => 'Miscarriage'],
    ];
    foreach ($tabs as $tabKey => $tabMeta):
        $isActive = ($activeTab === $tabKey);
        $activeClass = $isActive
            ? 'bg-slate-900 text-white border-slate-900'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
    ?>
    <a href="/HealthLogs/public/maternal/pregnancies/index.php?tab=<?= $tabKey ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border text-xs font-medium transition <?= $activeClass ?>">
      <?= $tabMeta['label'] ?>
      <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] <?= $isActive ? 'bg-white/20' : 'bg-slate-100 text-slate-600' ?>">
        <?= $tabCounts[$tabKey] ?>
      </span>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Pregnancy Records</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format($maternalSummary['total']) ?></div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Prenatal Visits</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format($maternalSummary['prenatal_visits']) ?></div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Postnatal Visits</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format($maternalSummary['postnatal_visits']) ?></div>
  </div>
</div>

<!-- Search bar -->
<form method="get" class="bg-white rounded-xl border border-slate-200 p-3 flex flex-col sm:flex-row gap-3 mb-4">
  <input type="hidden" name="tab" value="<?= h($activeTab) ?>" />
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fas fa-search text-xs"></i></span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 border rounded-lg py-2 text-sm" placeholder="Search patient name or barangay" />
  </div>
  <?php if ($activeTab === 'ongoing'): ?>
  <select name="stage" class="w-full sm:w-56 border rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Gestational Stages</option>
    <option value="sensitive" <?= $stageFilter === 'sensitive' ? 'selected' : '' ?>>Sensitive (6–7 Months)</option>
    <option value="late" <?= $stageFilter === 'late' ? 'selected' : '' ?>>Near Term (&gt;31 wks)</option>
    <option value="early" <?= $stageFilter === 'early' ? 'selected' : '' ?>>Early (&lt;24 wks)</option>
  </select>
  <?php endif; ?>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition" type="submit">Search</button>
    <a class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm text-center hover:bg-slate-50 transition" href="/HealthLogs/public/maternal/pregnancies/index.php?tab=<?= h($activeTab) ?>">Clear</a>
  </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
        <tr>
          <th class="text-left px-4 py-3 font-medium">Patient</th>
          <th class="text-left px-4 py-3 font-medium">Barangay</th>
          <th class="text-left px-4 py-3 font-medium">LMP Date</th>
          <th class="text-left px-4 py-3 font-medium">EDD Date</th>
          <th class="text-left px-4 py-3 font-medium">Gestational Progress</th>
          <th class="text-left px-4 py-3 font-medium">Status</th>
          <th class="text-right px-4 py-3 font-medium">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
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
              
              $rowClass = "hover:bg-slate-50 transition";
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
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    <?= $weeks ?> wks (~<?= $months ?> mos) · Sensitive
                  </span>
                <?php elseif ($isNearTerm): ?>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    <?= $weeks ?> wks (~<?= $months ?> mos) · 3rd Trimester
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
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">Ongoing</span>
                <?php elseif ($r['status'] === 'delivered'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">Delivered</span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">Miscarriage</span>
                <?php endif; // end status badge ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                <div class="inline-flex items-center gap-1.5">
                  <a href="/HealthLogs/public/maternal/pregnancies/view.php?id=<?= (int)$r['id'] ?>" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium">
                    View
                  </a>
                <?php if (can_manage_clinical_records()): ?>
                    <?php if ($r['status'] === 'ongoing'): ?>
                    <a href="/HealthLogs/public/maternal/prenatal/form.php?pregnancy_id=<?= (int)$r['id'] ?>" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium" title="Record prenatal checkup">
                      Prenatal Visit
                    </a>
                    <?php elseif ($r['status'] === 'delivered'): ?>
                    <a href="/HealthLogs/public/maternal/postnatal/form.php?pregnancy_id=<?= (int)$r['id'] ?>" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium" title="Record postnatal checkup">
                      Postnatal Visit
                    </a>
                    <?php endif; ?>
                    <button type="button" class="pregnancy-modal-edit px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium" data-embed-url="/HealthLogs/public/maternal/pregnancies/form_embed.php?id=<?= (int)$r['id'] ?>">
                      Edit
                    </button>
                    <form method="post" action="/HealthLogs/public/maternal/pregnancies/delete.php" class="inline" data-confirm="Delete this record?" data-confirm-title="Delete pregnancy" data-confirm-cta="Yes, delete">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                      <button class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium">
                        Delete
                      </button>
                    </form>
                <?php else: ?>
                  <span class="sr-only">Read only</span>
                <?php endif; ?>
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
