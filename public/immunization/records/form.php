<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$presetPatientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$presetVaccineId = isset($_GET['vaccine_id']) ? (int)$_GET['vaccine_id'] : 0;
$presetDoseNo = isset($_GET['dose_no']) ? (int)$_GET['dose_no'] : 0;
$returnTo = trim($_GET['return_to'] ?? '');

$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM immunization_records WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rec) {
        $_SESSION['error_message'] = 'Immunization record not found';
        header('Location: /HealthLogs/public/immunization/records/index.php');
        exit;
    }
    $presetPatientId = (int)$rec['patient_id'];
    $presetVaccineId = (int)$rec['vaccine_id'];
    $presetDoseNo = (int)$rec['dose_no'];
}

// Fetch all active patients, prioritizing children (0-5 years)
$patients = $pdo->query("
    SELECT id, first_name, middle_name, last_name, suffix, sex, birth_date, barangay,
           TIMESTAMPDIFF(MONTH, birth_date, CURDATE()) AS age_months,
           DATEDIFF(CURDATE(), birth_date) AS age_days
    FROM patients 
    WHERE status = 'active'
    ORDER BY (birth_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)) DESC, last_name ASC, first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$patientsById = [];
foreach ($patients as $p) {
    $fullName = trim($p['last_name'] . ', ' . $p['first_name'] . ($p['middle_name'] ? ' ' . substr($p['middle_name'], 0, 1) . '.' : '') . ($p['suffix'] ? ' ' . $p['suffix'] : ''));
    $ageMonths = (int)$p['age_months'];
    $ageDays = (int)$p['age_days'];
    
    if ($ageDays < 30) {
        $ageText = $ageDays . ' day' . ($ageDays === 1 ? '' : 's') . ' old';
    } elseif ($ageMonths < 24) {
        $ageText = $ageMonths . ' month' . ($ageMonths === 1 ? '' : 's') . ' old';
    } else {
        $ageYears = floor($ageMonths / 12);
        $ageText = $ageYears . ' yr' . ($ageYears === 1 ? '' : 's') . ' old';
    }

    $patientsById[$p['id']] = [
        'id' => (int)$p['id'],
        'name' => $fullName,
        'first_name' => $p['first_name'],
        'last_name' => $p['last_name'],
        'birth_date' => $p['birth_date'],
        'birth_date_formatted' => !empty($p['birth_date']) ? date('M d, Y', strtotime($p['birth_date'])) : 'Unknown',
        'sex' => strtoupper(substr($p['sex'] ?? 'M', 0, 1)),
        'barangay' => $p['barangay'] ?: 'Barangay Tangcul',
        'age_months' => $ageMonths,
        'age_days' => $ageDays,
        'age_text' => $ageText,
        'is_child' => ($ageMonths <= 60),
    ];
}

// Fetch all vaccines
$vaccines = $pdo->query("SELECT id, name, code, recommended_min_age_months, recommended_max_age_months, doses_required FROM vaccines ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$vaccinesById = [];
foreach ($vaccines as $v) {
    $vaccinesById[$v['id']] = [
        'id' => (int)$v['id'],
        'name' => $v['name'],
        'code' => $v['code'],
        'doses_required' => (int)$v['doses_required'],
        'min_age' => (float)$v['recommended_min_age_months'],
        'max_age' => (float)$v['recommended_max_age_months'],
    ];
}

// Fetch existing immunization records grouped by patient
$recordsStmt = $pdo->query("
    SELECT r.id, r.patient_id, r.vaccine_id, r.dose_no, r.administered_on, r.administered_at, r.lot_no,
           v.name AS vaccine_name, v.code AS vaccine_code
    FROM immunization_records r
    JOIN vaccines v ON v.id = r.vaccine_id
    ORDER BY r.administered_on ASC, r.dose_no ASC
");
$recordsByPatient = [];
while ($r = $recordsStmt->fetch(PDO::FETCH_ASSOC)) {
    $pId = (int)$r['patient_id'];
    $recordsByPatient[$pId][] = [
        'id' => (int)$r['id'],
        'vaccine_id' => (int)$r['vaccine_id'],
        'vaccine_name' => $r['vaccine_name'],
        'vaccine_code' => $r['vaccine_code'],
        'dose_no' => (int)$r['dose_no'],
        'administered_on' => $r['administered_on'],
        'date_formatted' => date('M d, Y', strtotime($r['administered_on'])),
        'lot_no' => $r['lot_no'] ?? '',
    ];
}

$selectedPatientId = $presetPatientId ?: (int)old('patient_id', $rec['patient_id'] ?? 0);
if (!$selectedPatientId && !empty($patients)) {
    $selectedPatientId = (int)$patients[0]['id'];
}
$selectedVaccineId = $presetVaccineId ?: (int)old('vaccine_id', $rec['vaccine_id'] ?? 1);
$selectedDoseNo = $presetDoseNo ?: (int)old('dose_no', $rec['dose_no'] ?? 1);
$adminDate = old('administered_on', $rec['administered_on'] ?? date('Y-m-d'));
$adminTime = old('administered_time', !empty($rec['administered_at']) ? date('H:i', strtotime($rec['administered_at'])) : date('H:i'));

$pageTitle = $rec ? 'Edit Immunization Record' : 'Log Administered Vaccine Dose';
require __DIR__ . '/../../partials/header.php';
?>

<style>
  .dose-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
  }
</style>

<div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow max-w-4xl mx-auto">
  <!-- Header Banner -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-6 border-b border-slate-100 gap-2">
    <div>
      <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200 mb-1">
        <i class="fas fa-syringe"></i> DOH TCL-2 EPI System
      </div>
      <h1 class="text-2xl font-bold text-slate-900"><?= h($pageTitle) ?></h1>
      <p class="text-xs text-slate-500 mt-0.5">Record vaccine administration with automatic dose tracking and TCL-2 register synchronization.</p>
    </div>
    <?php if ($returnTo === 'tcl'): ?>
      <a href="/HealthLogs/public/immunization/tcl.php" class="text-xs text-slate-500 hover:text-slate-800 font-semibold">
        &larr; Back to TCL-2 Register
      </a>
    <?php else: ?>
      <a href="/HealthLogs/public/immunization/records/index.php" class="text-xs text-slate-500 hover:text-slate-800 font-semibold">
        &larr; Back to Records List
      </a>
    <?php endif; ?>
  </div>

  <?php display_flash_messages(); ?>

  <form method="post" action="/HealthLogs/public/immunization/records/save.php" id="immForm" class="space-y-5">
    <?php if ($returnTo): ?><input type="hidden" name="return_to" value="<?= h($returnTo) ?>"><?php endif; ?>
    <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>

    <!-- 1. Patient Selection with Age Badge -->
    <div>
      <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
        Select Infant / Child Patient <span class="text-rose-500">*</span>
      </label>
      <select name="patient_id" id="patientSelect" required data-searchable-select data-search-placeholder="Search child by name, birthdate, or purok..." class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
        <?php foreach ($patients as $p): ?>
          <?php 
            $pInfo = $patientsById[$p['id']];
            $tag = $pInfo['is_child'] ? '👶 [Child ' . $pInfo['age_text'] . ']' : '[Adult]';
            $label = "{$tag} {$pInfo['name']} • Born: {$pInfo['birth_date_formatted']} • {$pInfo['barangay']}";
            $sel = ($p['id'] == $selectedPatientId) ? 'selected' : '';
          ?>
          <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Child Immunization History Banner (Dynamically Updated) -->
    <div id="childProfileCard" class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <span class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-xs">
            <i class="fas fa-baby"></i>
          </span>
          <div>
            <div class="font-bold text-sm text-slate-900" id="childCardName">--</div>
            <div class="text-xs text-slate-500" id="childCardMeta">--</div>
          </div>
        </div>
        <div id="childCardBadge" class="self-start sm:self-auto"></div>
      </div>

      <div class="mt-3">
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
          Administered Doses Summary
        </div>
        <div id="childDosesGrid" class="flex flex-wrap gap-2 text-xs">
          <!-- Populated via JS -->
        </div>
      </div>
    </div>

    <!-- 2. Vaccine & Dose Selection -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Vaccine to Administer <span class="text-rose-500">*</span>
        </label>
        <select name="vaccine_id" id="vaccineSelect" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
          <?php foreach ($vaccines as $v): ?>
            <?php $sel = ($v['id'] == $selectedVaccineId) ? 'selected' : ''; ?>
            <option value="<?= (int)$v['id'] ?>" <?= $sel ?> data-doses="<?= (int)$v['doses_required'] ?>" data-code="<?= h($v['code']) ?>" data-name="<?= h($v['name']) ?>">
              <?= h($v['name']) ?> (<?= h($v['code']) ?>) &bull; <?= (int)$v['doses_required'] ?> <?= (int)$v['doses_required'] > 1 ? 'doses' : 'dose' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="vaccineScheduleHint" class="text-xs text-teal-700 mt-1 font-medium"></div>
      </div>

      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Dose Number <span class="text-rose-500">*</span>
        </label>
        <select name="dose_no" id="doseSelect" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
          <!-- Dynamically populated based on vaccine and patient history -->
        </select>
        <div id="doseStatusHint" class="text-xs text-slate-500 mt-1"></div>
      </div>
    </div>

    <!-- 3. Administration Date, Time & Batch -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Date Administered <span class="text-rose-500">*</span>
        </label>
        <input name="administered_on" id="adminDateInput" type="date" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 bg-white font-mono" value="<?= h($adminDate) ?>" max="<?= date('Y-m-d') ?>" />
      </div>

      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Time Administered
        </label>
        <input name="administered_time" type="time" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 bg-white font-mono" value="<?= h($adminTime) ?>" />
      </div>

      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Lot / Batch Number
        </label>
        <input name="lot_no" type="text" placeholder="e.g. VAC-2026-09A" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 bg-white" value="<?= h(old('lot_no', $rec['lot_no'] ?? '')) ?>" />
      </div>
    </div>

    <!-- 4. Notes & Clinical Remarks -->
    <div>
      <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
        Clinical Notes / Reaction / Remarks
      </label>
      <textarea name="notes" placeholder="Site of injection (e.g. left deltoid, right anterolateral thigh), batch details, or child condition..." class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 bg-white" rows="2"><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
    </div>

    <!-- Live TCL-2 Mapping Indicator -->
    <div id="tclTargetCard" class="p-3.5 rounded-xl bg-teal-50/70 border border-teal-200 text-teal-900 text-xs flex items-center gap-2.5">
      <i class="fas fa-table-list text-teal-600 text-base shrink-0"></i>
      <div>
        <strong>TCL-2 Register Synchronization:</strong>
        <span id="tclTargetText">This record will be saved and displayed under the corresponding vaccine column in the official Target Client List.</span>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
      <?php if ($returnTo === 'tcl'): ?>
        <a class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/immunization/tcl.php">
          Cancel
        </a>
      <?php else: ?>
        <a class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/immunization/records/index.php">
          Cancel
        </a>
      <?php endif; ?>
      <button class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md transition inline-flex items-center gap-2" type="submit">
        <i class="fas fa-check"></i> Save Immunization Record
      </button>
    </div>
  </form>
</div>

<script>
  const patientsData = <?= json_encode($patientsById) ?>;
  const vaccinesData = <?= json_encode($vaccinesById) ?>;
  const existingRecords = <?= json_encode($recordsByPatient) ?>;
  let initialDoseNo = <?= json_encode($selectedDoseNo) ?>;

  const patientSelect = document.getElementById('patientSelect');
  const vaccineSelect = document.getElementById('vaccineSelect');
  const doseSelect = document.getElementById('doseSelect');
  const childCardName = document.getElementById('childCardName');
  const childCardMeta = document.getElementById('childCardMeta');
  const childCardBadge = document.getElementById('childCardBadge');
  const childDosesGrid = document.getElementById('childDosesGrid');
  const vaccineScheduleHint = document.getElementById('vaccineScheduleHint');
  const doseStatusHint = document.getElementById('doseStatusHint');
  const tclTargetText = document.getElementById('tclTargetText');

  function updateChildProfile() {
    const patientId = patientSelect.value;
    const patient = patientsData[patientId];
    if (!patient) return;

    childCardName.textContent = patient.name;
    childCardMeta.textContent = `Date of Birth: ${patient.birth_date_formatted} • Age: ${patient.age_text} • Sex: ${patient.sex} • ${patient.barangay}`;
    
    if (patient.is_child) {
      childCardBadge.innerHTML = `<span class="dose-badge bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fas fa-check-circle"></i> Eligible Child (0-5 yrs)</span>`;
    } else {
      childCardBadge.innerHTML = `<span class="dose-badge bg-amber-100 text-amber-800 border border-amber-300"><i class="fas fa-user"></i> Non-pediatric record</span>`;
    }

    const recs = existingRecords[patientId] || [];
    childDosesGrid.innerHTML = '';

    if (recs.length === 0) {
      childDosesGrid.innerHTML = '<span class="text-slate-400 italic">No vaccines recorded yet for this child.</span>';
    } else {
      recs.forEach(r => {
        const badge = document.createElement('span');
        badge.className = 'dose-badge bg-white border border-slate-300 text-slate-700 shadow-2xs';
        badge.innerHTML = `<i class="fas fa-check-circle text-emerald-500"></i> <strong>${r.vaccine_code}</strong> Dose ${r.dose_no} (${r.date_formatted})`;
        childDosesGrid.appendChild(badge);
      });
    }

    updateDoseOptions();
  }

  function updateDoseOptions() {
    const patientId = patientSelect.value;
    const vaccineId = vaccineSelect.value;
    const vaccine = vaccinesData[vaccineId];
    if (!vaccine) return;

    const recs = existingRecords[patientId] || [];
    const givenDoses = recs.filter(r => r.vaccine_id == vaccineId).map(r => r.dose_no);

    // Vaccine schedule guide
    let scheduleGuide = '';
    if (vaccine.code === 'BCG') scheduleGuide = 'Standard Schedule: At Birth / 0-28 days (1 dose)';
    else if (vaccine.code === 'HEPB') scheduleGuide = 'Standard Schedule: Birth dose within 24 hours (1 dose)';
    else if (vaccine.code === 'PENTA') scheduleGuide = 'Standard Schedule: 1 ½, 2 ½, and 3 ½ months (3 doses)';
    else if (vaccine.code === 'OPV') scheduleGuide = 'Standard Schedule: 1 ½, 2 ½, and 3 ½ months (3 doses)';
    else if (vaccine.code === 'IPV') scheduleGuide = 'Standard Schedule: 3 ½ months and 9 months (2 doses)';
    else if (vaccine.code === 'PCV') scheduleGuide = 'Standard Schedule: 1 ½, 2 ½, and 3 ½ months (3 doses)';
    else if (vaccine.code === 'MMR' || vaccine.code === 'MR') scheduleGuide = 'Standard Schedule: 9 months (Dose 1) and 12 months (Dose 2)';
    else scheduleGuide = `Recommended for ${vaccine.doses_required} dose(s)`;

    vaccineScheduleHint.textContent = scheduleGuide;

    // Populate Dose selector
    doseSelect.innerHTML = '';
    let nextDueDose = 1;
    for (let d = 1; d <= vaccine.doses_required; d++) {
      if (!givenDoses.includes(d)) {
        nextDueDose = d;
        break;
      }
    }

    for (let d = 1; d <= Math.max(vaccine.doses_required, 1); d++) {
      const isGiven = givenDoses.includes(d);
      const opt = document.createElement('option');
      opt.value = d;
      opt.textContent = `Dose ${d}` + (isGiven ? ' (Already Given)' : (d === nextDueDose ? ' ★ Next Due' : ''));
      if (isGiven) {
        opt.className = 'text-slate-400 bg-slate-50';
      }
      doseSelect.appendChild(opt);
    }

    if (initialDoseNo && initialDoseNo <= vaccine.doses_required) {
      doseSelect.value = initialDoseNo;
      initialDoseNo = null; // reset
    } else {
      doseSelect.value = nextDueDose;
    }

    updateTclPreview();
  }

  function updateTclPreview() {
    const vaccineId = vaccineSelect.value;
    const vaccine = vaccinesData[vaccineId];
    const doseNo = doseSelect.value;
    if (!vaccine) return;

    let colName = `${vaccine.name} (Dose ${doseNo})`;
    if (vaccine.code === 'BCG') colName = 'BCG (within 0-28 days / 29d-1yr)';
    else if (vaccine.code === 'HEPB') colName = 'Hepa B (within 24h / >24h-14d)';
    else if (vaccine.code === 'PENTA') colName = `DPT-HiB-HepB Dose ${doseNo}`;
    else if (vaccine.code === 'OPV') colName = `OPV Dose ${doseNo}`;
    else if (vaccine.code === 'IPV') colName = `IPV Dose ${doseNo}`;
    else if (vaccine.code === 'PCV') colName = `PCV Dose ${doseNo}`;
    else if (vaccine.code === 'MMR' || vaccine.code === 'MR') colName = `MMR Dose ${doseNo} (${doseNo == 1 ? '9 mos' : '12 mos'})`;

    tclTargetText.innerHTML = `This record will be automatically populated into the official <strong>TCL-2 Register</strong> under <strong>${colName}</strong>.`;
  }

  patientSelect.addEventListener('change', updateChildProfile);
  vaccineSelect.addEventListener('change', updateDoseOptions);
  doseSelect.addEventListener('change', updateTclPreview);

  // Initial load
  updateChildProfile();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
