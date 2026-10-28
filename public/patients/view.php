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
      Print Patient Summary
    </a>
    <a href="/HealthLogs/public/appointments/index.php?patient_id=<?= (int)$patient['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
      Schedule Appointment
    </a>
    <?php if (can_manage_clinical_records()): ?>
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
    <div><dt class="text-slate-500">Emergency Contact</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h($patient['emergency_contact_name'] ?: '—') ?><?= $patient['emergency_contact_phone'] ? ' · ' . h($patient['emergency_contact_phone']) : '' ?></dd></div>
    <div><dt class="text-slate-500">Classification</dt><dd class="font-medium text-slate-900 mt-0.5"><?= h(str_replace('_', ' ', $patient['classification'] ?? '—')) ?></dd></div>
    <div><dt class="text-slate-500">4Ps Beneficiary</dt><dd class="font-medium text-slate-900 mt-0.5"><?= !empty($patient['is_4ps']) ? 'Yes' : 'No' ?></dd></div>
    <div><dt class="text-slate-500">PWD</dt><dd class="font-medium text-slate-900 mt-0.5"><?= !empty($patient['is_pwd']) ? 'Yes' : 'No' ?></dd></div>
    <div><dt class="text-slate-500">Registered</dt><dd class="font-medium text-slate-900 mt-0.5"><?= date('M d, Y', strtotime($patient['created_at'])) ?></dd></div>
  </dl>
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
})();
</script>
