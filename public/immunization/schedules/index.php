<?php
$pageTitle = 'Immunization Schedules';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$clauses = [];
$params = [];
if ($q !== '') {
    $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR v.name LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
if (in_array($statusFilter, ['scheduled', 'completed', 'missed', 'cancelled'], true)) {
    $clauses[] = "s.status = ?";
    $params[] = $statusFilter;
}
$where = !empty($clauses) ? "WHERE " . implode(' AND ', $clauses) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM immunization_schedule s JOIN patients p ON p.id = s.patient_id JOIN vaccines v ON v.id = s.vaccine_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 20);

$stmt = $pdo->prepare("SELECT s.*, p.first_name, p.last_name, p.birth_date, v.name AS vaccine_name
    FROM immunization_schedule s
    JOIN patients p ON p.id = s.patient_id
    JOIN vaccines v ON v.id = s.vaccine_id
    $where ORDER BY s.scheduled_date DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();

$stats = $pdo->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) AS missed
    FROM immunization_schedule")->fetch();
?>
<?php display_flash_messages(); ?>

<!-- Page Header -->
<div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-semibold text-teal-600 uppercase tracking-widest"><i class="fas fa-calendar-check mr-1"></i> Immunization Module</div>
      <div class="text-xl font-bold text-slate-900 mt-0.5">Immunization Schedules</div>
      <p class="text-xs text-slate-500 mt-0.5">Vaccine dose schedule registry and tracking per patient.</p>
    </div>
    <div class="flex items-center gap-2">
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="scheduleModalOpenNew" data-embed-url="/HealthLogs/public/immunization/schedules/form_embed.php"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition shadow-xs">
          <i class="fas fa-plus text-xs"></i> Add Schedule
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- KPI Pills -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100 text-xs">
    <a href="/HealthLogs/public/immunization/schedules/index.php" class="p-3 rounded-xl border <?= empty($statusFilter) ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format((int)$stats['total']) ?></div>
      <div class="text-[11px] <?= empty($statusFilter) ? 'text-slate-300' : 'text-slate-500' ?>">Total</div>
    </a>
    <a href="?status=scheduled" class="p-3 rounded-xl border <?= $statusFilter === 'scheduled' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50/60 border-blue-200 hover:bg-blue-100 text-blue-800' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format((int)$stats['scheduled']) ?></div>
      <div class="text-[11px] <?= $statusFilter === 'scheduled' ? 'text-blue-100' : 'text-blue-600' ?>">Scheduled</div>
    </a>
    <a href="?status=completed" class="p-3 rounded-xl border <?= $statusFilter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100 text-emerald-800' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format((int)$stats['completed']) ?></div>
      <div class="text-[11px] <?= $statusFilter === 'completed' ? 'text-emerald-100' : 'text-emerald-600' ?>">Completed</div>
    </a>
    <a href="?status=missed" class="p-3 rounded-xl border <?= $statusFilter === 'missed' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50/60 border-rose-200 hover:bg-rose-100 text-rose-800' ?>">
      <div class="font-bold text-lg leading-tight"><?= number_format((int)$stats['missed']) ?></div>
      <div class="text-[11px] <?= $statusFilter === 'missed' ? 'text-rose-100' : 'text-rose-600' ?>">Missed</div>
    </a>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col md:flex-row gap-3 mb-5">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fas fa-search text-xs"></i></span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="Search patient name or vaccine" />
  </div>
  <select name="status" class="w-full md:w-48 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All statuses</option>
    <option value="scheduled"  <?= $statusFilter === 'scheduled'  ? 'selected' : '' ?>>Scheduled</option>
    <option value="completed"  <?= $statusFilter === 'completed'  ? 'selected' : '' ?>>Completed</option>
    <option value="missed"     <?= $statusFilter === 'missed'     ? 'selected' : '' ?>>Missed</option>
    <option value="cancelled"  <?= $statusFilter === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
  </select>
  <div class="flex gap-2">
    <button class="inline-flex items-center gap-1.5 bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs" type="submit"><i class="fas fa-filter text-xs"></i> Filter</button>
    <a class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition" href="/HealthLogs/public/immunization/schedules/index.php"><i class="fas fa-redo text-xs"></i> Clear</a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-5">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3.5 px-4">Patient</th>
          <th class="py-3.5 px-4">Age</th>
          <th class="py-3.5 px-4">Vaccine</th>
          <th class="py-3.5 px-4">Dose</th>
          <th class="py-3.5 px-4">Scheduled Date</th>
          <th class="py-3.5 px-4">Status</th>
          <th class="py-3.5 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-8 text-center text-slate-500" colspan="7">No schedules found matching the selected filters.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $s): ?>
            <?php
              $birthDate = new DateTime($s['birth_date']);
              $ageMonths = (int)$birthDate->diff(new DateTime())->m + ((int)$birthDate->diff(new DateTime())->y * 12);
              $statusBadge = match($s['status']) {
                  'scheduled'  => 'bg-blue-100 text-blue-800 border-blue-200',
                  'completed'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                  'missed'     => 'bg-rose-100 text-rose-800 border-rose-200',
                  'cancelled'  => 'bg-slate-100 text-slate-700 border-slate-200',
                  default      => 'bg-slate-100 text-slate-700 border-slate-200',
              };
              $isPast = strtotime($s['scheduled_date']) < strtotime('today') && $s['status'] === 'scheduled';
            ?>
            <tr class="hover:bg-slate-50/60 transition <?= $isPast ? 'bg-rose-50/30' : '' ?>">
              <td class="px-4 py-3 font-semibold text-slate-900"><?= h($s['last_name'] . ', ' . $s['first_name']) ?></td>
              <td class="px-4 py-3 text-slate-600"><?= $ageMonths ?> mos</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                  <?= h($s['vaccine_name']) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-slate-700 font-medium">Dose <?= h($s['dose_no']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap <?= $isPast ? 'text-rose-600 font-semibold' : 'text-slate-700' ?>">
                <?= date('M d, Y', strtotime($s['scheduled_date'])) ?>
                <?php if ($isPast): ?><div class="text-[10px] text-rose-500">Overdue</div><?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border <?= $statusBadge ?>">
                  <?= ucfirst(h($s['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <button type="button" class="schedule-modal-edit inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition mr-1"
                    data-embed-url="/HealthLogs/public/immunization/schedules/form_embed.php?id=<?= (int)$s['id'] ?>"><i class="fas fa-edit text-xs"></i> Edit</button>
                  <form method="post" action="/HealthLogs/public/immunization/schedules/delete.php" class="inline"
                    data-confirm="Delete this schedule?" data-confirm-title="Delete schedule" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>" />
                    <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold transition"><i class="fas fa-trash text-xs"></i> Delete</button>
                  </form>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 text-xs text-slate-400"><i class="fas fa-lock text-xs"></i> Read-Only</span>
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

<div id="scheduleFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="scheduleFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Immunization schedule form</div>
        <button type="button" id="scheduleFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="scheduleFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Immunization schedule form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('scheduleFormModal');
  var frame = document.getElementById('scheduleFormModalFrame');
  var backdrop = document.getElementById('scheduleFormModalBackdrop');
  var closeBtn = document.getElementById('scheduleFormModalClose');
  function openModal(url) { if (!modal || !frame || !url) return; frame.src = url; modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); if (closeBtn) closeBtn.focus(); }
  function closeModal() { if (!modal || !frame) return; frame.src = 'about:blank'; modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
  var newBtn = document.getElementById('scheduleModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.schedule-modal-edit').forEach(function (btn) { btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); }); });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
