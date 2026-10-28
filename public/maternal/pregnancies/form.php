<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM pregnancies WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Pregnancy record not found';
        header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
        exit;
    }
}

$patientSql = "
    SELECT id, first_name, last_name, birth_date, barangay
    FROM patients
    WHERE (sex = 'female'
      AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 20 AND 59)
";
$patientParams = [];
if ($rec) {
    $patientSql .= " OR id = ?";
    $patientParams[] = (int)$rec['patient_id'];
}
$patientSql .= " ORDER BY last_name ASC, first_name ASC";
$patientStmt = $pdo->prepare($patientSql);
$patientStmt->execute($patientParams);
$patients = $patientStmt->fetchAll(PDO::FETCH_ASSOC);
$pregnancyPatient = null;
if ($rec) {
    foreach ($patients as $patient) {
        if ((int)$patient['id'] === (int)$rec['patient_id']) {
            $pregnancyPatient = $patient;
            break;
        }
    }
}
$selectedPatientId = (int)old('patient_id', $rec['patient_id'] ?? 0);
if (!in_array($selectedPatientId, array_map('intval', array_column($patients, 'id')), true)) {
    $selectedPatientId = 0;
}

$pageTitle = $rec ? 'Edit Pregnancy' : 'New Pregnancy';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-5 rounded-xl border border-slate-200">
  <?php display_flash_messages(true, true); ?>
  <form method="post" action="/HealthLogs/public/maternal/pregnancies/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <?php if ($rec): ?>
    <div>
      <label class="block text-xs font-medium text-slate-700">Patient</label>
      <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800">
        <?php if ($pregnancyPatient): ?>
          <?= h($pregnancyPatient['last_name'] . ', ' . $pregnancyPatient['first_name'] . ' · ' . date('M d, Y', strtotime($pregnancyPatient['birth_date'])) . ' · ' . $pregnancyPatient['barangay']) ?>
        <?php else: ?>
          Patient information unavailable (ID <?= (int)$rec['patient_id'] ?>)
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div>
      <label for="motherPatientSelect" class="block text-xs font-medium text-slate-700">Select Patient <span class="text-rose-600">*</span></label>
      <p class="mt-1 text-xs text-slate-500">Choose a female patient aged 20–59. Confirm the selected name below before saving.</p>
      <select name="patient_id" id="motherPatientSelect" required data-searchable-select data-search-placeholder="Search adult female patient..." aria-describedby="selectedMotherSummary" class="mt-2 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
        <option value="" disabled <?= $selectedPatientId ? '' : 'selected' ?>>Select a patient</option>
        <?php foreach ($patients as $p): ?>
          <?php $sel = $selectedPatientId == $p['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= h($p['last_name'] . ', ' . $p['first_name'] . ' · ' . date('M d, Y', strtotime($p['birth_date'])) . ' · ' . $p['barangay']) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="selectedMotherSummary" aria-live="polite" class="mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
        No patient selected.
      </div>
    </div>
    <?php endif; ?>
    <div>
      <label class="block text-xs font-medium text-slate-700">Last Menstrual Period</label>
      <input name="lmp_date" type="date" required class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" value="<?= h(old('lmp_date', $rec['lmp_date'] ?? '')) ?>" />
      <div class="mt-1 text-xs text-slate-500">Start date of the last menstrual period.</div>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-700">Expected Delivery Date</label>
      <input name="edd_date" type="date" required class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" value="<?= h(old('edd_date', $rec['edd_date'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-700">Total Pregnancies</label>
      <input name="gravida" type="number" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" value="<?= h(old('gravida', $rec['gravida'] ?? '')) ?>" />
      <div class="mt-1 text-xs text-slate-500">How many times the patient has been pregnant.</div>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-700">Births</label>
      <input name="para" type="number" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" value="<?= h(old('para', $rec['para'] ?? '')) ?>" />
      <div class="mt-1 text-xs text-slate-500">Number of pregnancies that reached delivery.</div>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-700">Pregnancy Status</label>
      <?php $status = old('status', $rec['status'] ?? 'ongoing'); ?>
      <select name="status" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        <option value="ongoing" <?= ($status === 'ongoing') ? 'selected' : '' ?>>Ongoing</option>
        <option value="delivered" <?= ($status === 'delivered') ? 'selected' : '' ?>>Delivered</option>
        <option value="miscarriage" <?= ($status === 'miscarriage' || $status === 'terminated') ? 'selected' : '' ?>>Miscarriage</option>
      </select>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm" type="submit">Save</button>
      <a class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm" href="/HealthLogs/public/maternal/pregnancies/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php if (!$rec): ?>
<script>
(function () {
  var select = document.getElementById('motherPatientSelect');
  var summary = document.getElementById('selectedMotherSummary');
  if (!select || !summary) return;

  function updateSelectedPatient() {
    var option = select.options[select.selectedIndex];
    if (!option || !option.value) {
      summary.textContent = 'No patient selected. Select a patient above to continue.';
      summary.className = 'mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2.5 text-xs text-slate-500';
      return;
    }

    summary.textContent = 'Selected patient: ' + option.textContent;
    summary.className = 'mt-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-xs font-medium text-slate-800';
  }

  select.addEventListener('change', updateSelectedPatient);
  updateSelectedPatient();
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
