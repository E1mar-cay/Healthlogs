<?php
$pageTitle = 'Patient Record';
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    flash('error', 'Patient not found');
    header('Location: /HealthLogs/public/patients/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    flash('error', 'Patient record not found');
    header('Location: /HealthLogs/public/patients/index.php');
    exit;
}

$fullName = trim($patient['last_name'] . ', ' . $patient['first_name'] . ($patient['middle_name'] ? ' ' . $patient['middle_name'] : ''));
$isPrintMode = (isset($_GET['print']) && $_GET['print'] === '1');

// Fetch records from the service modules used by this patient.
$visitsStmt = $pdo->prepare("
    SELECT v.*, u.full_name AS staff_name
    FROM visits v
    LEFT JOIN users u ON u.id = v.recorded_by
    WHERE v.patient_id = ?
    ORDER BY v.visit_datetime DESC
");
$visitsStmt->execute([$id]);
$visits = $visitsStmt->fetchAll(PDO::FETCH_ASSOC);

// NCD service records
$ncdVisitsStmt = $pdo->prepare("
    SELECT nv.*, u.full_name AS staff_name
    FROM ncd_visits nv
    JOIN ncd_records nr ON nr.id = nv.ncd_record_id
    LEFT JOIN users u ON u.id = nv.recorded_by
    WHERE nr.patient_id = ?
    ORDER BY nv.visit_date DESC
");
$ncdVisitsStmt->execute([$id]);
$ncdVisits = $ncdVisitsStmt->fetchAll(PDO::FETCH_ASSOC);

// Maternal service records
$prenatalVisits = [];
$postnatalVisits = [];
if ($patient['sex'] === 'female') {
    $prenatalStmt = $pdo->prepare("
        SELECT pv.*, p.lmp_date, p.edd_date, u.full_name AS staff_name
        FROM prenatal_visits pv
        JOIN pregnancies p ON p.id = pv.pregnancy_id
        LEFT JOIN users u ON u.id = pv.recorded_by
        WHERE p.patient_id = ?
        ORDER BY pv.visit_datetime DESC
    ");
    $prenatalStmt->execute([$id]);
    $prenatalVisits = $prenatalStmt->fetchAll(PDO::FETCH_ASSOC);

    $postnatalStmt = $pdo->prepare("
        SELECT pv.*, u.full_name AS staff_name
        FROM postnatal_visits pv
        JOIN pregnancies p ON p.id = pv.pregnancy_id
        LEFT JOIN users u ON u.id = pv.recorded_by
        WHERE p.patient_id = ?
        ORDER BY pv.visit_datetime DESC
    ");
    $postnatalStmt->execute([$id]);
    $postnatalVisits = $postnatalStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Family planning service records
$fpVisits = [];
if ($patient['sex'] === 'female') {
    $fpStmt = $pdo->prepare("
        SELECT fv.*, fr.client_code, fr.method_accepted, u.full_name AS staff_name
        FROM fp_visits fv
        JOIN fp_records fr ON fr.id = fv.fp_record_id
        LEFT JOIN users u ON u.id = fv.recorded_by
        WHERE fr.patient_id = ?
        ORDER BY fv.visit_date DESC
    ");
    $fpStmt->execute([$id]);
    $fpVisits = $fpStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Immunization records
$immStmt = $pdo->prepare("
    SELECT ir.*, v.name AS vaccine_name, v.code AS vaccine_code, u.full_name AS administered_by_name
    FROM immunization_records ir
    JOIN vaccines v ON v.id = ir.vaccine_id
    LEFT JOIN users u ON u.id = ir.administered_by
    WHERE ir.patient_id = ?
    ORDER BY ir.administered_on DESC, ir.dose_no ASC
");
$immStmt->execute([$id]);
$immunizations = $immStmt->fetchAll(PDO::FETCH_ASSOC);

// Appointments
$aptStmt = $pdo->prepare("
    SELECT a.*, hs.service_name, hs.service_code, hs.category AS service_category, u.full_name AS booked_by_name
    FROM patient_appointments a
    JOIN health_services hs ON hs.id = a.service_id
    LEFT JOIN users u ON u.id = a.created_by
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$aptStmt->execute([$id]);
$appointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC);

// Diagnosed Conditions & Allergies
$condStmt = $pdo->prepare("
    SELECT * FROM patient_conditions
    WHERE patient_id = ?
    ORDER BY diagnosed_on DESC, id DESC
");
$condStmt->execute([$id]);
$patientConditions = $condStmt->fetchAll(PDO::FETCH_ASSOC);

$allergyStmt = $pdo->prepare("
    SELECT * FROM patient_allergies
    WHERE patient_id = ?
    ORDER BY noted_on DESC, id DESC
");
$allergyStmt->execute([$id]);
$patientAllergies = $allergyStmt->fetchAll(PDO::FETCH_ASSOC);

// Active Medicines with FEFO Stock for Walk-In Consultation Dispensing
$medsStmt = $pdo->query("
    SELECT m.id, m.name, m.unit,
           COALESCE(SUM(mt.quantity), 0) AS stock
    FROM medicines m
    JOIN medicine_batches mb ON mb.medicine_id = m.id AND mb.expiry_date >= CURDATE()
    JOIN medicine_transactions mt ON mt.batch_id = mb.id
    GROUP BY m.id, m.name, m.unit
    HAVING stock > 0
    ORDER BY m.name ASC
");
$availableMedicines = $medsStmt ? $medsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Handle print view
if ($isPrintMode) {
    include __DIR__ . '/_print_chart.php';
    exit;
}

require __DIR__ . '/../partials/header.php';
?>

<?php display_flash_messages(); ?>

<!-- Back and Actions Top Bar -->
<div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
  <div class="flex items-center gap-2">
    <a href="/HealthLogs/public/patients/index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition">
      <i class="fas fa-arrow-left text-teal-600"></i> Back to Registry
    </a>
    <span class="text-xs text-slate-400">/</span>
    <span class="text-xs font-medium text-slate-600">Patient #<?= (int)$patient['id'] ?></span>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <a target="_blank" href="/HealthLogs/public/patients/view.php?id=<?= (int)$patient['id'] ?>&print=1" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
      <i class="fas fa-print text-slate-500"></i> Print Patient Summary
    </a>
    <a href="/HealthLogs/public/appointments/index.php?patient_id=<?= (int)$patient['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
      <i class="fas fa-calendar-plus text-teal-600"></i> Schedule Appointment
    </a>
    <?php if (can_manage_clinical_records()): ?>
      <button type="button" id="consultationModalOpen" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shadow-xs">
        <i class="fas fa-stethoscope"></i> + Record Consultation / Diagnosis
      </button>
      <button type="button" class="patient-modal-edit inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition" data-embed-url="/HealthLogs/public/patients/form_embed.php?id=<?= (int)$patient['id'] ?>">
        Edit Profile
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Patient Master Profile Card -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
  <div class="flex flex-col gap-5">
    <div>
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-xl font-semibold text-slate-900"><?= h($fullName) ?></h1>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium border <?= $patient['status'] === 'active' ? 'bg-slate-50 text-slate-700 border-slate-200' : 'bg-slate-100 text-slate-600 border-slate-200' ?>">
          <?= ucfirst(h($patient['status'])) ?>
        </span>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-5 gap-y-3 text-xs">
      <div><span class="block text-slate-500">Age</span><span class="font-medium text-slate-900"><?= PatientClassifier::formatAge($patient['birth_date']) ?></span></div>
      <div><span class="block text-slate-500">Sex</span><span class="font-medium text-slate-900"><?= ucfirst(h($patient['sex'])) ?></span></div>
      <div><span class="block text-slate-500">Birth Date</span><span class="font-medium text-slate-900"><?= date('M d, Y', strtotime($patient['birth_date'])) ?></span></div>
      <div><span class="block text-slate-500">Barangay</span><span class="font-medium text-slate-900"><?= h($patient['barangay']) ?></span></div>
    </div>
  </div>
</div>

<!-- Tabs Navigation -->
<div class="mb-5 border-b border-slate-200">
  <nav class="flex space-x-5 overflow-x-auto text-xs font-medium" aria-label="Patient records">
    <a href="#overview" class="py-3 px-1 border-b-2 border-slate-900 text-slate-900 shrink-0">
      Patient Information
    </a>
    <a href="#service-records" class="py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-900 shrink-0">
      Service Records
    </a>
    <a href="#immunizations" class="py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-900 shrink-0">
      Immunizations (<?= count($immunizations) ?>)
    </a>
    <a href="#appointments" class="py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-900 shrink-0">
      Appointments (<?= count($appointments) ?>)
    </a>
  </nav>
</div>

<!-- Patient Information -->
<div id="overview" class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
  <h2 class="text-sm font-semibold text-slate-900 mb-4">Patient Information</h2>
  <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 text-xs">
    <div><dt class="text-slate-500">Address</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['address_line'] ? $patient['address_line'] . ', ' : '') ?><?= h($patient['barangay']) ?></dd></div>
    <div><dt class="text-slate-500">Sex</dt><dd class="font-medium text-slate-900 mt-0.5"><?= ucfirst(h($patient['sex'])) ?></dd></div>
    <div><dt class="text-slate-500">Contact Number</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['contact_no'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['email'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">Civil Status</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['civil_status'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">Blood Type</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['blood_type'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">PhilHealth PIN</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['philhealth_no'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">PhilHealth Category</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h(str_replace('_', ' ', $patient['philhealth_category'] ?? '—')) ?></dd></div>
    <div>
      <dt class="text-slate-500">Mother's Full Name</dt>
      <dd class="font-medium text-slate-900 mt-0.5">
        <?php if (!empty($patient['mother_name'])): ?>
          <?php if (!empty($patient['mother_patient_id'])): ?>
            <a href="/HealthLogs/public/patients/view.php?id=<?= (int)$patient['mother_patient_id'] ?>" class="text-teal-700 hover:underline font-semibold">
              <?= h($patient['mother_name']) ?> <i class="fas fa-arrow-up-right-from-square text-[10px] ml-0.5"></i>
            </a>
          <?php else: ?>
            <?= h($patient['mother_name']) ?>
          <?php endif; ?>
        <?php else: ?>
          —
        <?php endif; ?>
      </dd>
    </div>
    <div><dt class="text-slate-500">Father's Full Name</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['father_name'] ?? '') ?: '—' ?></dd></div>
    <div><dt class="text-slate-500">Legal Guardian Name</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['guardian_name'] ?? '') ?: '—' ?></dd></div>
    <div><dt class="text-slate-500">Parent / Guardian Contact</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['parent_contact_no'] ?? '') ?: '—' ?></dd></div>
    <div><dt class="text-slate-500">Emergency Contact</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['emergency_contact_name'] ?: '—') ?><?= $patient['emergency_contact_phone'] ? ' · ' . h($patient['emergency_contact_phone']) : '' ?></dd></div>
    <div><dt class="text-slate-500">Classification</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h(str_replace('_', ' ', $patient['classification'] ?? '—')) ?></dd></div>
    <div><dt class="text-slate-500">4Ps Beneficiary</dt><dd class="font-medium text-slate-900 mt-0.5"><?= !empty($patient['is_4ps']) ? 'Yes' : 'No' ?></dd></div>
    <div><dt class="text-slate-500">PWD</dt><dd class="font-medium text-slate-900 mt-0.5"><?= !empty($patient['is_pwd']) ? 'Yes' : 'No' ?></dd></div>
    <div><dt class="text-slate-500">Registered</dt><dd class="font-medium text-slate-900 mt-0.5"><?= date('M d, Y', strtotime($patient['created_at'])) ?></dd></div>
  </dl>

  <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
    <div>
      <div class="font-semibold text-slate-800 mb-2 flex items-center justify-between">
        <span><i class="fas fa-notes-medical text-teal-600 mr-1"></i> Diagnoses &amp; Medical Conditions (<?= count($patientConditions) ?>)</span>
      </div>
      <?php if (empty($patientConditions)): ?>
        <p class="text-slate-400 italic">No diagnosed medical conditions on file.</p>
      <?php else: ?>
        <div class="space-y-1.5">
          <?php foreach ($patientConditions as $cond): ?>
            <div class="flex items-start justify-between gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200/80">
              <div>
                <span class="font-semibold text-slate-900"><?= h($cond['condition_name']) ?></span>
                <?php if (!empty($cond['notes'])): ?>
                  <span class="block text-[11px] text-slate-500 mt-0.5"><?= h($cond['notes']) ?></span>
                <?php endif; ?>
              </div>
              <div class="text-right shrink-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase border <?= $cond['status'] === 'chronic' ? 'bg-amber-50 text-amber-800 border-amber-200' : ($cond['status'] === 'resolved' ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-teal-50 text-teal-800 border-teal-200') ?>">
                  <?= h($cond['status']) ?>
                </span>
                <?php if (!empty($cond['diagnosed_on'])): ?>
                  <span class="block text-[10px] text-slate-400 mt-0.5"><?= date('M d, Y', strtotime($cond['diagnosed_on'])) ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="font-semibold text-slate-800 mb-2 flex items-center justify-between">
        <span><i class="fas fa-triangle-exclamation text-amber-600 mr-1"></i> Known Allergies (<?= count($patientAllergies) ?>)</span>
      </div>
      <?php if (empty($patientAllergies)): ?>
        <p class="text-slate-400 italic">No known drug or food allergies recorded.</p>
      <?php else: ?>
        <div class="space-y-1.5">
          <?php foreach ($patientAllergies as $allergy): ?>
            <div class="flex items-start justify-between gap-2 p-2 rounded-lg bg-amber-50/50 border border-amber-200/80">
              <div>
                <span class="font-semibold text-slate-900"><?= h($allergy['allergen']) ?></span>
                <?php if (!empty($allergy['reaction'])): ?>
                  <span class="block text-[11px] text-slate-600 mt-0.5">Reaction: <?= h($allergy['reaction']) ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($allergy['noted_on'])): ?>
                <span class="text-[10px] text-slate-400 shrink-0"><?= date('M d, Y', strtotime($allergy['noted_on'])) ?></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
  $serviceRecords = [];
  foreach ($visits as $v) {
      $serviceRecords[] = [
          'type' => ucfirst(str_replace('_', ' ', $v['visit_type'])),
          'date' => $v['visit_datetime'],
          'title' => $v['reason'] ?: ucfirst(str_replace('_', ' ', $v['visit_type'])) . ' service',
          'details' => $v['notes'] ?: '',
          'staff' => $v['staff_name'] ?: '',
      ];
  }
  foreach ($ncdVisits as $nv) {
      $details = [];
      if ($nv['bp_systolic'] && $nv['bp_diastolic']) $details[] = "BP: {$nv['bp_systolic']}/{$nv['bp_diastolic']}";
      if ($nv['blood_sugar_mgdl']) $details[] = "Blood sugar: {$nv['blood_sugar_mgdl']} mg/dL" . ($nv['blood_sugar_type'] ? ' (' . strtoupper($nv['blood_sugar_type']) . ')' : '');
      if ($nv['weight_kg']) $details[] = "Weight: {$nv['weight_kg']} kg";
      if ($nv['medications_dispensed']) $details[] = "Medication: {$nv['medications_dispensed']}";
      if ($nv['findings_complaints']) $details[] = $nv['findings_complaints'];
      if ($nv['management_plan']) $details[] = $nv['management_plan'];
      $serviceRecords[] = [
          'type' => 'NCD',
          'date' => $nv['visit_date'],
          'title' => 'NCD Follow-up',
          'details' => implode(' · ', $details),
          'staff' => $nv['staff_name'] ?: '',
      ];
  }
  foreach ($prenatalVisits as $pv) {
      $details = [];
      if ($pv['bp_systolic'] && $pv['bp_diastolic']) $details[] = "BP: {$pv['bp_systolic']}/{$pv['bp_diastolic']}";
      if ($pv['weight_kg']) $details[] = "Weight: {$pv['weight_kg']} kg";
      if ($pv['notes']) $details[] = $pv['notes'];
      $serviceRecords[] = [
          'type' => 'Prenatal',
          'date' => $pv['visit_datetime'],
          'title' => 'Prenatal Visit' . ($pv['gestational_age_weeks'] ? " · {$pv['gestational_age_weeks']} weeks" : ''),
          'details' => implode(' · ', $details),
          'staff' => $pv['staff_name'] ?: '',
      ];
  }
  foreach ($postnatalVisits as $pnv) {
      $details = [];
      if ($pnv['mother_condition']) $details[] = 'Mother: ' . $pnv['mother_condition'];
      if ($pnv['baby_condition']) $details[] = 'Baby: ' . $pnv['baby_condition'];
      if ($pnv['notes']) $details[] = $pnv['notes'];
      $serviceRecords[] = [
          'type' => 'Postnatal',
          'date' => $pnv['visit_datetime'],
          'title' => 'Postnatal Visit',
          'details' => implode(' · ', $details),
          'staff' => $pnv['staff_name'] ?: '',
      ];
  }
  foreach ($fpVisits as $fv) {
      $details = [];
      if ($fv['method_prescribed']) $details[] = 'Method: ' . strtoupper(str_replace('_', ' ', $fv['method_prescribed']));
      if ($fv['bp_systolic'] && $fv['bp_diastolic']) $details[] = "BP: {$fv['bp_systolic']}/{$fv['bp_diastolic']}";
      if ($fv['weight_kg']) $details[] = "Weight: {$fv['weight_kg']} kg";
      if ($fv['findings_complaints']) $details[] = $fv['findings_complaints'];
      $serviceRecords[] = [
          'type' => 'Family Planning',
          'date' => $fv['visit_date'],
          'title' => 'Family Planning Visit',
          'details' => implode(' · ', $details),
          'staff' => $fv['staff_name'] ?: '',
      ];
  }
  usort($serviceRecords, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
?>

<div id="service-records" class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
  <div class="flex items-center justify-between gap-3 mb-4">
    <h2 class="text-sm font-semibold text-slate-900">Service Records</h2>
    <span class="text-xs text-slate-500"><?= count($serviceRecords) ?> record<?= count($serviceRecords) === 1 ? '' : 's' ?></span>
  </div>

  <?php if (empty($serviceRecords)): ?>
    <p class="py-6 text-center text-xs text-slate-500">No service records found.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="border-b border-slate-200 text-left text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Date</th>
            <th class="px-3 py-2 font-medium">Service</th>
            <th class="px-3 py-2 font-medium">Details</th>
            <th class="px-3 py-2 font-medium">Recorded By</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($serviceRecords as $record): ?>
            <tr>
              <td class="px-3 py-3 text-slate-700 whitespace-nowrap">
                <?= date('M d, Y', strtotime($record['date'])) ?>
                <?php if (strlen($record['date']) > 10): ?><span class="block text-slate-500"><?= date('h:i A', strtotime($record['date'])) ?></span><?php endif; ?>
              </td>
              <td class="px-3 py-3 font-medium text-slate-900">
                <span class="block text-slate-500 font-normal"><?= h($record['type']) ?></span>
                <?= h($record['title']) ?>
              </td>
              <td class="px-3 py-3 text-slate-600"><?= h($record['details'] ?: '—') ?></td>
              <td class="px-3 py-3 text-slate-600 whitespace-nowrap"><?= h($record['staff'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Immunization Records -->
<div id="immunizations" class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-900">Immunization Records</h2>
  </div>

  <?php if (empty($immunizations)): ?>
    <p class="py-6 text-center text-xs text-slate-500">No immunization records found.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="border-b border-slate-200 text-slate-500 font-medium">
          <tr>
            <th class="text-left px-4 py-3">Vaccine</th>
            <th class="text-left px-4 py-3">Dose No</th>
            <th class="text-left px-4 py-3">Status</th>
            <th class="text-left px-4 py-3">Date Administered</th>
            <th class="text-left px-4 py-3">Lot / Batch No</th>
            <th class="text-left px-4 py-3">Administered By</th>
            <th class="text-left px-4 py-3">Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($immunizations as $imm): ?>
            <tr>
              <td class="px-4 py-3 font-medium text-slate-900">
                <?= h($imm['vaccine_name']) ?>
              </td>
              <td class="px-4 py-3">
                Dose <?= (int)$imm['dose_no'] ?>
              </td>
              <td class="px-4 py-3">
                Administered
              </td>
              <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                <?= date('M d, Y', strtotime($imm['administered_on'])) ?>
              </td>
              <td class="px-4 py-3 font-mono text-slate-600">
                <?= h($imm['lot_no'] ?: '—') ?>
              </td>
              <td class="px-4 py-3 text-slate-600">
                <?= h($imm['administered_by_name'] ?: '—') ?>
              </td>
              <td class="px-4 py-3 text-slate-500">
                <?= h($imm['notes'] ?: '—') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Appointments -->
<div id="appointments" class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-900">Appointments</h2>
    <a href="/HealthLogs/public/appointments/index.php?patient_id=<?= (int)$patient['id'] ?>" class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
      Book Appointment
    </a>
  </div>

  <?php if (empty($appointments)): ?>
    <p class="py-6 text-center text-xs text-slate-500">No appointments found.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="border-b border-slate-200 text-slate-500 font-medium">
          <tr>
            <th class="text-left px-3 py-2">Code</th>
            <th class="text-left px-3 py-2">Service</th>
            <th class="text-left px-3 py-2">Date & Time</th>
            <th class="text-left px-3 py-2">Assigned Staff</th>
            <th class="text-left px-3 py-2">Status</th>
            <th class="text-left px-3 py-2">Reason / Notes</th>
            <th class="text-right px-3 py-2">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($appointments as $apt): ?>
            <?php
              $statusBadge = 'bg-slate-50 text-slate-700 border-slate-200';
            ?>
            <tr>
              <td class="px-3 py-3 font-mono font-medium text-slate-900">
                <?= h($apt['appointment_code']) ?>
              </td>
              <td class="px-3 py-3 font-medium text-slate-800">
                <?= h($apt['service_name']) ?>
                <span class="text-[10px] text-slate-400 uppercase font-mono block"><?= h($apt['service_code']) ?></span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span class="font-medium text-slate-800"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span>
                <span class="text-[11px] text-slate-500 block"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
              </td>
              <td class="px-3 py-3 text-slate-600 whitespace-nowrap">
                <?= h($apt['assigned_personnel']) ?>
              </td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border <?= $statusBadge ?>">
                  <?= ucfirst(h($apt['status'])) ?>
                </span>
                <?php if ($apt['status'] === 'rescheduled' && $apt['previous_appointment_date']): ?>
                  <div class="text-[10px] text-slate-500 mt-0.5">Previous: <?= date('M d, Y', strtotime($apt['previous_appointment_date'])) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-3 py-3 max-w-xs text-slate-600">
                <?php if ($apt['reason']): ?>
                  <div><strong>Reason:</strong> <?= h($apt['reason']) ?></div>
                <?php endif; ?>
                <?php if ($apt['clinical_notes']): ?>
                  <div class="mt-1"><strong>Notes:</strong> <?= h($apt['clinical_notes']) ?></div>
                <?php endif; ?>
                <?php if ($apt['cancellation_reason']): ?>
                  <div class="mt-1"><strong>Cancelled:</strong> <?= h($apt['cancellation_reason']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-3 py-3 text-right whitespace-nowrap">
                <a href="/HealthLogs/public/appointments/index.php?q=<?= urlencode($apt['appointment_code']) ?>" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                  Manage in Appointments &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div id="patientFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="patientFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-3 sm:mt-6 max-w-6xl px-2 sm:px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Edit Patient Profile</div>
        <button type="button" id="patientFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="patientFormModalFrame" class="w-full min-h-[75vh] border-0 flex-1" title="Patient form"></iframe>
    </div>
  </div>
</div>

<!-- MODAL: RECORD WALK-IN CONSULTATION / DIAGNOSIS / ALLERGY -->
<?php if (can_manage_clinical_records()): ?>
<div id="consultationModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" id="consultationModalBackdrop"></div>
  <div class="relative z-10 mx-auto mt-6 sm:mt-10 max-w-2xl px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
        <div>
          <h3 class="font-bold text-slate-900 text-base">Record Clinical Consultation &amp; Diagnosis</h3>
          <p class="text-xs text-slate-500 mt-0.5">Patient: <strong><?= h($fullName) ?></strong> (#<?= (int)$patient['id'] ?>)</p>
        </div>
        <button type="button" id="consultationModalClose" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
      </div>

      <form method="post" action="/HealthLogs/public/patients/save_consultation.php" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto text-xs">
        <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 uppercase mb-1">Visit Date &amp; Time</label>
            <input type="datetime-local" name="visit_datetime" value="<?= date('Y-m-d\TH:i') ?>" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 uppercase mb-1">Consultation Type</label>
            <select name="visit_type" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
              <option value="general">General Walk-In Consultation</option>
              <option value="ncd">NCD / Chronic Check-up</option>
              <option value="prenatal">Prenatal Check-up</option>
              <option value="postnatal">Postnatal Check-up</option>
              <option value="family_planning">Family Planning Counseling</option>
              <option value="immunization">Immunization Assessment</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 uppercase mb-1">Chief Complaint / Consultation Reason</label>
          <input type="text" name="reason" placeholder="e.g. Acute Upper Respiratory Infection, BP monitoring, Wound dressing..." class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 uppercase mb-1">Clinical Findings, Vitals &amp; Management Notes</label>
          <textarea name="notes" rows="2" placeholder="e.g. BP 120/80, Temp 37.8°C. Advised oral hydration and rest..." class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"></textarea>
        </div>

        <!-- Optional Diagnosis / Medical Condition -->
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
          <div class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">
            <i class="fas fa-notes-medical text-teal-600 mr-1"></i> Add Diagnosed Condition (Optional — Updates Morbidity &amp; Seasonal Reports)
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
              <label class="block font-medium text-slate-600 mb-1">Disease / Condition Name</label>
              <input type="text" name="condition_name" list="commonConditionsList" placeholder="e.g. Hypertension, Asthma, Acute Gastroenteritis..." class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white" />
              <datalist id="commonConditionsList">
                <option value="Hypertension"></option>
                <option value="Diabetes Type 2"></option>
                <option value="Asthma"></option>
                <option value="Allergic Rhinitis"></option>
                <option value="Arthritis"></option>
                <option value="Migraine"></option>
                <option value="Acute Upper Respiratory Infection"></option>
                <option value="Acute Gastroenteritis"></option>
                <option value="Urinary Tract Infection"></option>
              </datalist>
            </div>
            <div>
              <label class="block font-medium text-slate-600 mb-1">Condition Status</label>
              <select name="condition_status" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white">
                <option value="active">Active</option>
                <option value="chronic">Chronic</option>
                <option value="resolved">Resolved</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Optional Allergy -->
        <div class="p-3.5 rounded-xl bg-amber-50/40 border border-amber-200/80 space-y-2">
          <div class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">
            <i class="fas fa-triangle-exclamation text-amber-600 mr-1"></i> Record New Allergy (Optional)
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-600 mb-1">Allergen (Drug / Food)</label>
              <input type="text" name="allergen" placeholder="e.g. Penicillin, Ibuprofen, Seafood..." class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white" />
            </div>
            <div>
              <label class="block font-medium text-slate-600 mb-1">Reaction</label>
              <input type="text" name="reaction" placeholder="e.g. Urticaria / rashes, mild angioedema..." class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white" />
            </div>
          </div>
        </div>

        <!-- Optional Pharmacy Dispensing (FEFO) & Follow-up Date (Stacked cleanly) -->
        <div class="space-y-3 pt-1">
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-3 min-w-0">
              <label class="block font-semibold text-slate-700 uppercase mb-1">Dispense Medicine from Pharmacy (FEFO)</label>
              <select name="dispense_medicine_id" class="w-full min-w-0 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white truncate">
                <option value="0">— No medicine dispensed —</option>
                <?php foreach ($availableMedicines as $med): ?>
                  <option value="<?= (int)$med['id'] ?>">
                    <?= h($med['name']) ?> (<?= (int)$med['stock'] ?> <?= h($med['unit'] ?: 'units') ?> avail)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="min-w-0">
              <label class="block font-semibold text-slate-700 uppercase mb-1">Quantity</label>
              <input type="number" name="dispense_quantity" min="0" max="500" value="0" placeholder="Qty" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" title="Quantity to dispense" />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 uppercase mb-1">Follow-up Appointment Date (Optional)</label>
            <input type="date" name="next_visit_date" min="<?= date('Y-m-d') ?>" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white" />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <button type="button" id="consultationModalCancel" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
            <i class="fas fa-check mr-1"></i> Save Clinical Record
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  var modal = document.getElementById('patientFormModal');
  var frame = document.getElementById('patientFormModalFrame');
  var backdrop = document.getElementById('patientFormModalBackdrop');
  var closeBtn = document.getElementById('patientFormModalClose');

  function openModal(url) {
    if (!modal || !frame) return;
    frame.src = url;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeModal() {
    if (!modal || !frame) return;
    modal.classList.add('hidden');
    frame.src = 'about:blank';
    document.body.classList.remove('overflow-hidden');
  }

  document.querySelectorAll('.patient-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var u = btn.getAttribute('data-embed-url');
      if (u) openModal(u);
    });
  });

  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  var consultModal = document.getElementById('consultationModal');
  var consultOpen = document.getElementById('consultationModalOpen');
  var consultClose = document.getElementById('consultationModalClose');
  var consultCancel = document.getElementById('consultationModalCancel');
  var consultBackdrop = document.getElementById('consultationModalBackdrop');

  function closeConsultModal() {
    if (!consultModal) return;
    consultModal.classList.add('hidden');
  }

  if (consultOpen && consultModal) {
    consultOpen.addEventListener('click', function () {
      consultModal.classList.remove('hidden');
    });
  }
  if (consultClose) consultClose.addEventListener('click', closeConsultModal);
  if (consultCancel) consultCancel.addEventListener('click', closeConsultModal);
  if (consultBackdrop) consultBackdrop.addEventListener('click', closeConsultModal);
})();
</script>
