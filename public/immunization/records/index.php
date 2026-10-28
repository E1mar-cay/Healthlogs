<?php
$pageTitle = 'Immunization Records';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$vaccineFilter = (int)($_GET['vaccine_id'] ?? 0);
$where = '';
$params = [];
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
if (!empty($clauses)) {
    $where = "WHERE " . implode(' AND ', $clauses);
}

$vaccines = $pdo->query("SELECT id, name FROM vaccines ORDER BY name ASC")->fetchAll();

$summary = $pdo->query("
    SELECT COUNT(*) AS administered_doses,
           COUNT(DISTINCT r.patient_id) AS patients_vaccinated,
           COUNT(DISTINCT CASE
               WHEN p.birth_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
                AND p.birth_date <= CURDATE()
               THEN p.id
           END) AS children_vaccinated,
           SUM(CASE
               WHEN r.administered_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND r.administered_on < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)
               THEN 1 ELSE 0
           END) AS doses_this_month
    FROM immunization_records r
    JOIN patients p ON p.id = r.patient_id
")->fetch(PDO::FETCH_ASSOC);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM immunization_records r JOIN patients p ON p.id = r.patient_id JOIN vaccines v ON v.id = r.vaccine_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);

$stmt = $pdo->prepare("SELECT r.*, p.first_name, p.last_name, p.sex, p.birth_date, p.barangay,
        v.doses_required,
        TIMESTAMPDIFF(MONTH, p.birth_date, CURDATE()) AS patient_age_months,
        (SELECT COUNT(DISTINCT r2.dose_no)
         FROM immunization_records r2
         WHERE r2.patient_id = r.patient_id AND r2.vaccine_id = r.vaccine_id) AS administered_dose_count,
        (SELECT r3.id
         FROM immunization_records r3
         WHERE r3.patient_id = r.patient_id AND r3.vaccine_id = r.vaccine_id
         ORDER BY r3.dose_no DESC, r3.administered_on DESC, r3.id DESC
         LIMIT 1) AS latest_record_id
    FROM immunization_records r
    JOIN patients p ON p.id = r.patient_id
    JOIN vaccines v ON v.id = r.vaccine_id
    $where ORDER BY r.administered_on DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php display_flash_messages(); ?>

<!-- Page Header -->
<div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-semibold text-slate-500 uppercase tracking-widest"><i class="fas fa-syringe mr-1"></i> Child Health &bull; EPI Program</div>
      <div class="text-xl font-bold text-slate-900 mt-0.5">Immunization Records</div>
      <p class="text-xs text-slate-500 mt-0.5">Patient information for administered records. View a patient chart for vaccine and dose status.</p>
    </div>
    <div class="flex items-center gap-2">
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="recordModalOpenNew" data-embed-url="/HealthLogs/public/immunization/records/form_embed.php"
          class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold transition shadow-xs">
          <i class="fas fa-plus mr-1"></i> New Record
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Children Vaccinated <span class="text-slate-400">(under 5)</span></div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format((int)$summary['children_vaccinated']) ?></div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Patients Vaccinated</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format((int)$summary['patients_vaccinated']) ?></div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Administered Doses</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format((int)$summary['administered_doses']) ?></div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-slate-200">
    <div class="text-xs text-slate-500">Doses This Month</div>
    <div class="text-2xl font-semibold text-slate-900 mt-1"><?= number_format((int)$summary['doses_this_month']) ?></div>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col md:flex-row gap-3 mb-5">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fas fa-search text-xs"></i></span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="Search patient name or vaccine" />
  </div>
  <select name="vaccine_id" class="w-full md:w-64 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="0">All vaccines</option>
    <?php foreach ($vaccines as $vaccine): ?>
      <option value="<?= (int)$vaccine['id'] ?>" <?= $vaccineFilter === (int)$vaccine['id'] ? 'selected' : '' ?>><?= h($vaccine['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-800 transition" type="submit">Filter</button>
    <a class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition" href="/HealthLogs/public/immunization/records/index.php">Clear</a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-5">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3.5 px-4">Patient</th>
          <th class="py-3.5 px-4">Sex</th>
          <th class="py-3.5 px-4">Date of Birth</th>
          <th class="py-3.5 px-4">Purok</th>
          <th class="py-3.5 px-4">Date Administered</th>
          <th class="py-3.5 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-8 text-center text-slate-500" colspan="6">No immunization records found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-slate-50/60 transition">
              <td class="px-4 py-3 font-semibold text-slate-900"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
              <td class="px-4 py-3 text-slate-600 capitalize"><?= h($r['sex']) ?></td>
              <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= h(date('M d, Y', strtotime($r['birth_date']))) ?></td>
              <td class="px-4 py-3 text-slate-600"><?= h($r['barangay'] ?: '—') ?></td>
              <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                <?= isset($r['administered_on']) ? date('M d, Y', strtotime($r['administered_on'])) : h($r['administered_at'] ?? '—') ?>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <a href="/HealthLogs/public/patients/view.php?id=<?= (int)$r['patient_id'] ?>#immunizations" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold mr-1">
                  <i class="fas fa-eye text-[10px]"></i> View
                </a>
                <?php if (can_manage_clinical_records()): ?>
                  <?php if ((int)$r['patient_age_months'] >= 0 && (int)$r['patient_age_months'] < 12 && (int)$r['id'] === (int)$r['latest_record_id'] && (int)$r['administered_dose_count'] < (int)$r['doses_required']): ?>
                    <button type="button" class="record-modal-new-dose px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold mr-1"
                      data-embed-url="/HealthLogs/public/immunization/records/form_embed.php?<?= h(http_build_query(['patient_id' => (int)$r['patient_id'], 'vaccine_id' => (int)$r['vaccine_id']])) ?>">
                      <i class="fas fa-syringe text-[10px]"></i> Take Another Dose
                    </button>
                  <?php endif; ?>
                  <button type="button" class="record-modal-edit px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold mr-1"
                    data-embed-url="/HealthLogs/public/immunization/records/form_embed.php?id=<?= (int)$r['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/immunization/records/delete.php" class="inline"
                    data-confirm="Delete this immunization record?" data-confirm-title="Delete record" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
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
  function openModal(url) { if (!modal || !frame || !url) return; frame.src = url; modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); if (closeBtn) closeBtn.focus(); }
  function closeModal() { if (!modal || !frame) return; frame.src = 'about:blank'; modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
  var newBtn = document.getElementById('recordModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.record-modal-new-dose').forEach(function (btn) { btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); }); });
  document.querySelectorAll('.record-modal-edit').forEach(function (btn) { btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); }); });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
