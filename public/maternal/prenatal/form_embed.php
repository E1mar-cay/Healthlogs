<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedPregnancyId = (int)($_GET['pregnancy_id'] ?? 0);
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM prenatal_visits WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Prenatal visit not found';
        header('Location: /HealthLogs/public/maternal/prenatal/index.php');
        exit;
    }
}
$linkedPregnancyId = $rec ? (int)$rec['pregnancy_id'] : $selectedPregnancyId;
$pregStmt = $pdo->prepare("
    SELECT pr.id, pr.lmp_date, pr.edd_date, pr.status, p.first_name, p.last_name
    FROM pregnancies pr
    JOIN patients p ON p.id = pr.patient_id
    WHERE pr.status = 'ongoing' OR pr.id = ?
    ORDER BY pr.id DESC
");
$pregStmt->execute([$linkedPregnancyId]);
$pregs = $pregStmt->fetchAll(PDO::FETCH_ASSOC);
$selectedPregnancy = null;
foreach ($pregs as $pregnancy) {
    if ((int)$pregnancy['id'] === $linkedPregnancyId) {
        $selectedPregnancy = $pregnancy;
        break;
    }
}
$title = $rec ? 'Edit Prenatal Visit' : 'New Prenatal Visit';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base target="_parent">
  <title><?= h($title) ?></title>
  <script src="/HealthLogs/public/assets/js/tailwind.js"></script>
  <script src="/HealthLogs/public/assets/js/sweetalert2.all.min.js"></script>
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/card-icons.css">
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <div class="mb-5">
      <h1 class="text-xl font-semibold"><?= h($title) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Document prenatal monitoring and notes.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/maternal/prenatal/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <?php if ($selectedPregnancy): ?>
        <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
          <input type="hidden" name="pregnancy_id" value="<?= (int)$selectedPregnancy['id'] ?>" />
          <input type="hidden" name="return_pregnancy_id" value="<?= (int)$selectedPregnancy['id'] ?>" />
          <div class="text-xs font-medium text-slate-500">Pregnancy record</div>
          <div class="mt-1 font-semibold text-slate-900"><?= h($selectedPregnancy['last_name'] . ', ' . $selectedPregnancy['first_name']) ?></div>
          <div class="mt-1 text-xs text-slate-600">LMP <?= h(date('M d, Y', strtotime($selectedPregnancy['lmp_date']))) ?> · EDD <?= h(date('M d, Y', strtotime($selectedPregnancy['edd_date']))) ?></div>
        </div>
      <?php else: ?>
        <div>
          <label class="block text-sm text-slate-600" for="prenatalPregnancy">Select ongoing pregnancy</label>
          <select id="prenatalPregnancy" name="pregnancy_id" required data-searchable-select data-search-placeholder="Search mother name..." class="mt-1 w-full border rounded px-3 py-2">
            <option value="">Choose a mother</option>
            <?php foreach ($pregs as $p): ?>
              <option value="<?= (int)$p['id'] ?>" data-lmp="<?= h($p['lmp_date']) ?>" <?= old('pregnancy_id', $linkedPregnancyId) == $p['id'] ? 'selected' : '' ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?> · LMP <?= h($p['lmp_date']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600" for="prenatalVisitDate">Checkup date and time</label>
        <input id="prenatalVisitDate" name="visit_datetime" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('visit_datetime', str_replace(' ', 'T', $rec['visit_datetime'] ?? ''))) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600" for="prenatalGestationalAge">Pregnancy age at checkup (weeks)</label>
        <input id="prenatalGestationalAge" name="gestational_age_weeks" type="number" min="1" max="45" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('gestational_age_weeks', $rec['gestational_age_weeks'] ?? '')) ?>" <?= $selectedPregnancy ? 'data-lmp="' . h($selectedPregnancy['lmp_date']) . '"' : '' ?> />
        <p class="mt-1 text-xs text-slate-500">Used for 8-ANC tracking. Visit ranges: 8–12, 13–20, 21–27, 28–30, 31–34, 35, 36, and 37+ weeks. The date can fill this in automatically.</p>
      </div>
      <div>
        <label class="block text-sm text-slate-600">BP Systolic</label>
        <input name="bp_systolic" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('bp_systolic', $rec['bp_systolic'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">BP Diastolic</label>
        <input name="bp_diastolic" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('bp_diastolic', $rec['bp_diastolic'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Weight (kg)</label>
        <input name="weight_kg" type="number" step="0.01" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('weight_kg', $rec['weight_kg'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Schedule next checkup (optional)</label>
        <input name="next_appointment_date" type="date" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('next_appointment_date', '')) ?>" />
        <p class="mt-1 text-xs text-slate-500">Adding a date also creates an appointment and reminder.</p>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Follow-up time (optional)</label>
        <input name="next_appointment_time" type="time" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('next_appointment_time', '')) ?>" />
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600">Notes</label>
        <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save checkup</button>
        <a class="text-slate-600" href="<?= $linkedPregnancyId ? '/HealthLogs/public/maternal/tcl.php?pregnancy_id=' . $linkedPregnancyId : '/HealthLogs/public/maternal/prenatal/index.php' ?>">Cancel</a>
      </div>
    </form>
  </div>
  <script src="/HealthLogs/public/assets/js/card-icons.js"></script>
</body>
<script src="/HealthLogs/public/assets/js/searchable-select.js"></script>
<script>
(() => {
  const dateInput = document.getElementById('prenatalVisitDate');
  const ageInput = document.getElementById('prenatalGestationalAge');
  const pregnancySelect = document.getElementById('prenatalPregnancy');
  if (!dateInput || !ageInput) return;

  let ageWasEdited = ageInput.value !== '';
  ageInput.addEventListener('input', () => { ageWasEdited = true; });
  const updateGestationalAge = () => {
    if (ageWasEdited || !dateInput.value) return;
    const selectedOption = pregnancySelect && pregnancySelect.selectedOptions[0];
    const lmpDate = selectedOption ? selectedOption.dataset.lmp : ageInput.dataset.lmp;
    if (!lmpDate) return;
    const lmp = new Date(lmpDate + 'T00:00:00');
    const visit = new Date(dateInput.value);
    const elapsedDays = Math.floor((visit.getTime() - lmp.getTime()) / 86400000);
    if (elapsedDays >= 0) ageInput.value = Math.floor(elapsedDays / 7);
  };
  dateInput.addEventListener('change', updateGestationalAge);
  if (pregnancySelect) pregnancySelect.addEventListener('change', updateGestationalAge);
})();
</script>
</html>
