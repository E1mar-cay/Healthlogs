<?php
$pageTitle = 'Patient Master Record';
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

// 1. Fetch Health History: Allergies & Conditions
$allergiesStmt = $pdo->prepare("SELECT * FROM patient_allergies WHERE patient_id = ? ORDER BY noted_on DESC, id DESC");
$allergiesStmt->execute([$id]);
$allergies = $allergiesStmt->fetchAll(PDO::FETCH_ASSOC);

$conditionsStmt = $pdo->prepare("SELECT * FROM patient_conditions WHERE patient_id = ? ORDER BY diagnosed_on DESC, id DESC");
$conditionsStmt->execute([$id]);
$conditions = $conditionsStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Consultation Records
$visitsStmt = $pdo->prepare("
    SELECT v.*, u.full_name AS staff_name
    FROM visits v
    LEFT JOIN users u ON u.id = v.recorded_by
    WHERE v.patient_id = ?
    ORDER BY v.visit_datetime DESC
");
$visitsStmt->execute([$id]);
$visits = $visitsStmt->fetchAll(PDO::FETCH_ASSOC);

// NCD Consultations if applicable
$ncdVisitsStmt = $pdo->prepare("
    SELECT nv.*, nr.diagnosis_type, nr.ncd_code, u.full_name AS staff_name
    FROM ncd_visits nv
    JOIN ncd_records nr ON nr.id = nv.ncd_record_id
    LEFT JOIN users u ON u.id = nv.recorded_by
    WHERE nr.patient_id = ?
    ORDER BY nv.visit_date DESC
");
$ncdVisitsStmt->execute([$id]);
$ncdVisits = $ncdVisitsStmt->fetchAll(PDO::FETCH_ASSOC);

// Maternal Prenatal & Postnatal Visits if applicable
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

// Family Planning Consultations if applicable
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

// 3. Immunization Records
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

// 4. Appointments & Follow-ups
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

// Reminders
$remindersStmt = $pdo->prepare("
    SELECT * FROM reminders WHERE patient_id = ? ORDER BY due_date DESC LIMIT 10
");
$remindersStmt->execute([$id]);
$patientReminders = $remindersStmt->fetchAll(PDO::FETCH_ASSOC);

// Available services for booking
$services = $pdo->query("SELECT id, service_code, service_name, category FROM health_services WHERE is_active = 1 ORDER BY service_name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
    <span class="text-xs font-semibold text-slate-600">Patient Chart #<?= (int)$patient['id'] ?></span>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <a target="_blank" href="/HealthLogs/public/patients/view.php?id=<?= (int)$patient['id'] ?>&print=1" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition">
      <i class="fas fa-print text-teal-600"></i> Print Clinical Chart
    </a>
    <a href="/HealthLogs/public/appointments/index.php?patient_id=<?= (int)$patient['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold shadow-xs transition">
      <i class="fas fa-calendar-plus"></i> Schedule Appointment
    </a>
    <?php if (can_manage_clinical_records()): ?>
      <button type="button" class="patient-modal-edit inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition" data-embed-url="/HealthLogs/public/patients/form_embed.php?id=<?= (int)$patient['id'] ?>">
        <i class="fas fa-pen-to-square"></i> Edit Profile
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Patient Master Profile Card -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
  <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
    <div class="flex items-start gap-4">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white flex items-center justify-center font-bold text-2xl shadow-sm shrink-0">
        <?= substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1) ?>
      </div>
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-2xl font-bold text-slate-900"><?= h($fullName) ?></h1>
          <span class="px-2 py-0.5 rounded-full text-xs font-semibold border <?= $patient['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200' ?>">
            <?= ucfirst(h($patient['status'])) ?>
          </span>
        </div>
        <div class="flex flex-wrap items-center gap-2 mt-2">
          <!-- Classification Badge with exact age -->
          <?= PatientClassifier::renderBadge($patient['classification'] ?? 'adult', $patient['birth_date']) ?>
          
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
            <i class="fas fa-venus-mars text-[10px] mr-1 text-slate-500"></i> <?= ucfirst(h($patient['sex'])) ?>
          </span>

          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
            <i class="fas fa-map-pin text-[10px] mr-1 text-slate-500"></i> <?= h($patient['barangay']) ?>
          </span>

          <?php if (!empty($patient['is_4ps'])): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
              <i class="fas fa-hand-holding-heart text-[10px] mr-1"></i> 4Ps Beneficiary
            </span>
          <?php endif; ?>

          <?php if (!empty($patient['is_pwd'])): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
              <i class="fas fa-wheelchair text-[10px] mr-1"></i> PWD
            </span>
          <?php endif; ?>

          <?php if (!empty($patient['blood_type'])): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
              <i class="fas fa-droplet text-[10px] mr-1"></i> Blood <?= h($patient['blood_type']) ?>
            </span>
          <?php endif; ?>
        </div>
        <p class="text-xs text-slate-500 mt-2">
          <?= h(PatientClassifier::getInfo($patient['classification'] ?? 'adult')['description']) ?>
        </p>
      </div>
    </div>

    <!-- Quick Vital Info Box -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs shrink-0 md:min-w-[340px]">
      <div>
        <span class="text-slate-400 block text-[10px] uppercase font-bold">Birth Date</span>
        <span class="font-semibold text-slate-800"><?= date('M d, Y', strtotime($patient['birth_date'])) ?></span>
      </div>
      <div>
        <span class="text-slate-400 block text-[10px] uppercase font-bold">Exact Age</span>
        <span class="font-bold text-teal-700"><?= PatientClassifier::formatAge($patient['birth_date']) ?></span>
      </div>
      <div>
        <span class="text-slate-400 block text-[10px] uppercase font-bold">Civil Status</span>
        <span class="font-semibold text-slate-800 capitalize"><?= h($patient['civil_status'] ?? 'Single') ?></span>
      </div>
      <div>
        <span class="text-slate-400 block text-[10px] uppercase font-bold">PhilHealth PIN</span>
        <span class="font-mono font-semibold text-slate-800"><?= h($patient['philhealth_no'] ?: 'Non-Member') ?></span>
      </div>
      <div>
        <span class="text-slate-400 block text-[10px] uppercase font-bold">Contact No</span>
        <span class="font-semibold text-slate-800"><?= h($patient['contact_no'] ?: '—') ?></span>
      </div>
      <div>
        <span class="text-slate-400 block text-[10px] uppercase font-bold">Emergency Contact</span>
        <span class="font-semibold text-slate-800 truncate block"><?= h($patient['emergency_contact_name'] ?: '—') ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Tabs Navigation -->
<div class="mb-6 border-b border-slate-200">
  <nav class="flex space-x-6 overflow-x-auto text-xs font-semibold" aria-label="Tabs">
    <a href="#overview" class="py-3 px-1 border-b-2 border-teal-600 text-teal-700 flex items-center gap-1.5 shrink-0">
      <i class="fas fa-clipboard-user"></i> Profile & History
    </a>
    <a href="#consultations" class="py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 shrink-0">
      <i class="fas fa-stethoscope"></i> Consultations (<?= count($visits) + count($ncdVisits) + count($prenatalVisits) + count($postnatalVisits) + count($fpVisits) ?>)
    </a>
    <a href="#immunizations" class="py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 shrink-0">
      <i class="fas fa-syringe"></i> Immunizations (<?= count($immunizations) ?>)
    </a>
    <a href="#appointments" class="py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 shrink-0">
      <i class="fas fa-calendar-check"></i> Appointments (<?= count($appointments) ?>)
    </a>
  </nav>
</div>

<!-- SECTION 1: Health History & Profile Overview -->
<div id="overview" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
  <!-- Demographics & PhilHealth -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
      <i class="fas fa-address-card text-teal-600"></i> Demographic Details
    </div>
    <dl class="divide-y divide-slate-100 text-xs">
      <div class="py-2 flex justify-between">
        <dt class="text-slate-500">Address / Purok</dt>
        <dd class="font-semibold text-slate-800 text-right"><?= h($patient['address_line'] ? $patient['address_line'] . ', ' : '') ?><?= h($patient['barangay']) ?></dd>
      </div>
      <div class="py-2 flex justify-between">
        <dt class="text-slate-500">Email Address</dt>
        <dd class="font-semibold text-slate-800 text-right"><?= h($patient['email'] ?: '—') ?></dd>
      </div>
      <div class="py-2 flex justify-between">
        <dt class="text-slate-500">PhilHealth Category</dt>
        <dd class="font-semibold text-slate-800 text-right capitalize"><?= str_replace('_', ' ', h($patient['philhealth_category'] ?? 'non_member')) ?></dd>
      </div>
      <div class="py-2 flex justify-between">
        <dt class="text-slate-500">Emergency Phone</dt>
        <dd class="font-semibold text-slate-800 text-right"><?= h($patient['emergency_contact_phone'] ?: '—') ?></dd>
      </div>
      <div class="py-2 flex justify-between">
        <dt class="text-slate-500">Registered On</dt>
        <dd class="font-semibold text-slate-800 text-right"><?= date('M d, Y', strtotime($patient['created_at'])) ?></dd>
      </div>
    </dl>
  </div>

  <!-- Known Allergies -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center justify-between">
      <span class="flex items-center gap-1.5"><i class="fas fa-hand-dots text-rose-600"></i> Allergies & Reactions</span>
      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= empty($allergies) ? 'bg-slate-100 text-slate-600' : 'bg-rose-100 text-rose-800' ?>">
        <?= count($allergies) ?> Noted
      </span>
    </div>
    <?php if (empty($allergies)): ?>
      <div class="p-6 text-center text-slate-400 text-xs">
        <i class="fas fa-shield-virus text-2xl text-slate-300 mb-2 block"></i>
        No known allergies documented.
      </div>
    <?php else: ?>
      <ul class="divide-y divide-slate-100 text-xs">
        <?php foreach ($allergies as $a): ?>
          <li class="py-2.5">
            <div class="font-bold text-rose-700 flex items-center gap-1">
              <i class="fas fa-circle-exclamation text-[10px]"></i> <?= h($a['allergen']) ?>
            </div>
            <div class="text-slate-500 text-[11px] mt-0.5">Reaction: <?= h($a['reaction'] ?: 'Not specified') ?></div>
            <?php if ($a['noted_on']): ?>
              <div class="text-slate-400 text-[10px] mt-0.5">Noted on: <?= date('M d, Y', strtotime($a['noted_on'])) ?></div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Chronic Conditions & Diagnoses -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center justify-between">
      <span class="flex items-center gap-1.5"><i class="fas fa-notes-medical text-teal-600"></i> Conditions & Diagnoses</span>
      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= empty($conditions) ? 'bg-slate-100 text-slate-600' : 'bg-teal-100 text-teal-800' ?>">
        <?= count($conditions) ?> Active
      </span>
    </div>
    <?php if (empty($conditions)): ?>
      <div class="p-6 text-center text-slate-400 text-xs">
        <i class="fas fa-heart-circle-check text-2xl text-emerald-400 mb-2 block"></i>
        No active chronic diagnoses documented.
      </div>
    <?php else: ?>
      <ul class="divide-y divide-slate-100 text-xs">
        <?php foreach ($conditions as $c): ?>
          <li class="py-2.5">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900"><?= h($c['condition_name']) ?></span>
              <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold <?= $c['status'] === 'active' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600' ?>">
                <?= ucfirst(h($c['status'])) ?>
              </span>
            </div>
            <?php if (!empty($c['notes'])): ?>
              <div class="text-slate-500 text-[11px] mt-0.5"><?= h($c['notes']) ?></div>
            <?php endif; ?>
            <?php if ($c['diagnosed_on']): ?>
              <div class="text-slate-400 text-[10px] mt-0.5">Diagnosed: <?= date('M d, Y', strtotime($c['diagnosed_on'])) ?></div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<!-- SECTION 2: Consultations & RHU Services Received -->
<div id="consultations" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
    <div>
      <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
        <i class="fas fa-stethoscope text-teal-600"></i> Clinical Consultations & Services Rendered
      </h2>
      <p class="text-xs text-slate-500 mt-0.5">Chronological record of healthcare services provided by Barangay Tangcul Rural Health Center.</p>
    </div>
  </div>

  <?php
    $allEncounters = [];
    foreach ($visits as $v) {
        $allEncounters[] = [
            'type' => 'General Consultation',
            'date' => $v['visit_datetime'],
            'title' => $v['reason'] ?: 'Routine Primary Care Consultation',
            'details' => $v['notes'] ?: 'Standard physical assessment and consultation.',
            'staff' => $v['staff_name'] ?? 'Health Center Staff',
            'badge' => 'bg-blue-100 text-blue-800'
        ];
    }
    foreach ($ncdVisits as $nv) {
        $vitals = [];
        if ($nv['bp_systolic'] && $nv['bp_diastolic']) $vitals[] = "BP: {$nv['bp_systolic']}/{$nv['bp_diastolic']}";
        if ($nv['blood_sugar_mgdl']) $vitals[] = "Sugar: {$nv['blood_sugar_mgdl']} mg/dL (" . strtoupper($nv['blood_sugar_type'] ?? '') . ")";
        if ($nv['weight_kg']) $vitals[] = "Wt: {$nv['weight_kg']} kg";
        $vitalsText = !empty($vitals) ? implode(' | ', $vitals) : '';

        $allEncounters[] = [
            'type' => 'NCD Maintenance Checkup',
            'date' => $nv['visit_date'] . ' 09:00:00',
            'title' => 'NCD Consultation (' . ucfirst(str_replace('_', ' ', $nv['diagnosis_type'])) . ')',
            'details' => ($vitalsText ? "{$vitalsText} • " : '') . ($nv['management_plan'] ?: $nv['findings_complaints'] ?: 'Maintenance checkup and adherence evaluation.'),
            'staff' => $nv['staff_name'] ?? 'BHW NCD Focal',
            'badge' => 'bg-purple-100 text-purple-800'
        ];
    }
    foreach ($prenatalVisits as $pv) {
        $allEncounters[] = [
            'type' => 'Prenatal Care Visit',
            'date' => $pv['visit_datetime'],
            'title' => 'Prenatal Checkup' . ($pv['gestational_age_weeks'] ? " ({$pv['gestational_age_weeks']} weeks AOG)" : ''),
            'details' => "BP: {$pv['bp_systolic']}/{$pv['bp_diastolic']} • Weight: {$pv['weight_kg']} kg • Notes: " . ($pv['notes'] ?: 'Maternal vital signs & fetal development assessment.'),
            'staff' => $pv['staff_name'] ?? 'Midwife',
            'badge' => 'bg-rose-100 text-rose-800'
        ];
    }
    foreach ($postnatalVisits as $pnv) {
        $allEncounters[] = [
            'type' => 'Postnatal Checkup',
            'date' => $pnv['visit_datetime'],
            'title' => 'Postpartum & Lactation Assessment',
            'details' => "Mother: {$pnv['mother_condition']} • Baby: {$pnv['baby_condition']} • Notes: " . ($pnv['notes'] ?: 'Lochia and breastfeeding check.'),
            'staff' => $pnv['staff_name'] ?? 'Midwife',
            'badge' => 'bg-fuchsia-100 text-fuchsia-800'
        ];
    }
    foreach ($fpVisits as $fv) {
        $allEncounters[] = [
            'type' => 'Family Planning Visit',
            'date' => $fv['visit_date'] . ' 10:00:00',
            'title' => 'Family Planning (' . strtoupper(str_replace('_', ' ', $fv['method_prescribed'])) . ')',
            'details' => "Contraceptive method issued • " . ($fv['findings_complaints'] ?: 'Routine resupply and side-effects monitoring.'),
            'staff' => $fv['staff_name'] ?? 'BHW FP Coordinator',
            'badge' => 'bg-indigo-100 text-indigo-800'
        ];
    }

    // Sort descending by date
    usort($allEncounters, function($a, $b) {
        return strtotime($b['date']) <=> strtotime($a['date']);
    });
  ?>

  <?php if (empty($allEncounters)): ?>
    <div class="p-8 text-center text-slate-400 text-xs">
      <i class="fas fa-notes-medical text-3xl text-slate-300 mb-2 block"></i>
      No clinical visits recorded for this patient yet.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
          <tr>
            <th class="text-left px-4 py-3">Date & Time</th>
            <th class="text-left px-4 py-3">Service Type</th>
            <th class="text-left px-4 py-3">Clinical Findings / Notes</th>
            <th class="text-left px-4 py-3">Attending Staff</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($allEncounters as $enc): ?>
            <tr class="hover:bg-slate-50/70 transition">
              <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                <?= date('M d, Y', strtotime($enc['date'])) ?>
                <span class="text-[10px] text-slate-400 block"><?= date('h:i A', strtotime($enc['date'])) ?></span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold <?= $enc['badge'] ?>">
                  <?= h($enc['type']) ?>
                </span>
                <div class="font-bold text-slate-900 mt-0.5"><?= h($enc['title']) ?></div>
              </td>
              <td class="px-4 py-3 text-slate-600 max-w-md">
                <?= h($enc['details']) ?>
              </td>
              <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                <i class="fas fa-user-doctor text-[10px] mr-1 text-slate-400"></i> <?= h($enc['staff']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- SECTION 3: Immunization Records & Vaccine Passport -->
<div id="immunizations" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
    <div>
      <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
        <i class="fas fa-syringe text-teal-600"></i> Immunization Records & Vaccine Card
      </h2>
      <p class="text-xs text-slate-500 mt-0.5">DOH Expanded Program on Immunization (EPI) and adult/senior vaccinations.</p>
    </div>
    <?php if ($patient['classification'] === 'infant' || $patient['classification'] === 'under_five'): ?>
      <span class="px-3 py-1 rounded-full text-xs font-bold bg-pink-50 text-pink-700 border border-pink-200">
        <i class="fas fa-baby mr-1"></i> Child Routine Immunization Target
      </span>
    <?php endif; ?>
  </div>

  <?php if (empty($immunizations)): ?>
    <div class="p-8 text-center text-slate-400 text-xs">
      <i class="fas fa-shield-virus text-3xl text-slate-300 mb-2 block"></i>
      No immunization administrations logged for this patient yet.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
          <tr>
            <th class="text-left px-4 py-3">Vaccine</th>
            <th class="text-left px-4 py-3">Dose No</th>
            <th class="text-left px-4 py-3">Date Administered</th>
            <th class="text-left px-4 py-3">Lot / Batch No</th>
            <th class="text-left px-4 py-3">Administered By</th>
            <th class="text-left px-4 py-3">Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($immunizations as $imm): ?>
            <tr class="hover:bg-slate-50/70 transition">
              <td class="px-4 py-3 font-bold text-slate-900">
                <?= h($imm['vaccine_name']) ?> (<?= h($imm['vaccine_code']) ?>)
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                  Dose <?= (int)$imm['dose_no'] ?>
                </span>
              </td>
              <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                <?= date('M d, Y', strtotime($imm['administered_on'])) ?>
              </td>
              <td class="px-4 py-3 font-mono text-slate-600">
                <?= h($imm['lot_no'] ?: '—') ?>
              </td>
              <td class="px-4 py-3 text-slate-600">
                <?= h($imm['administered_by_name'] ?? 'Healthcare Worker') ?>
              </td>
              <td class="px-4 py-3 text-slate-500">
                <?= h($imm['notes'] ?: 'Routine dose given per protocol') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- SECTION 4: Appointments, Reminders, and Follow-ups -->
<div id="appointments" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
    <div>
      <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
        <i class="fas fa-calendar-check text-teal-600"></i> Patient Appointments & Follow-up Tracking
      </h2>
      <p class="text-xs text-slate-500 mt-0.5">Manage scheduled visits, reschedules, and completion notes for required services.</p>
    </div>
    <a href="/HealthLogs/public/appointments/index.php?patient_id=<?= (int)$patient['id'] ?>" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition">
      <i class="fas fa-plus mr-1"></i> Book New Appointment
    </a>
  </div>

  <?php if (empty($appointments)): ?>
    <div class="p-8 text-center text-slate-400 text-xs">
      <i class="fas fa-calendar-xmark text-3xl text-slate-300 mb-2 block"></i>
      No appointments on record for this patient. Click "Book New Appointment" to schedule one.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
          <tr>
            <th class="text-left px-4 py-3">Code</th>
            <th class="text-left px-4 py-3">Service Required</th>
            <th class="text-left px-4 py-3">Date & Time</th>
            <th class="text-left px-4 py-3">Assigned Staff</th>
            <th class="text-left px-4 py-3">Status</th>
            <th class="text-left px-4 py-3">Reason / Clinical Notes</th>
            <th class="text-right px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($appointments as $apt): ?>
            <?php
              $statusBadge = match($apt['status']) {
                  'scheduled' => 'bg-blue-100 text-blue-800 border-blue-200',
                  'rescheduled' => 'bg-amber-100 text-amber-800 border-amber-200',
                  'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                  'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                  default => 'bg-slate-100 text-slate-700 border-slate-200',
              };
            ?>
            <tr class="hover:bg-slate-50/70 transition">
              <td class="px-4 py-3 font-mono font-bold text-slate-900">
                <?= h($apt['appointment_code']) ?>
              </td>
              <td class="px-4 py-3 font-semibold text-slate-800">
                <?= h($apt['service_name']) ?>
                <span class="text-[10px] text-slate-400 uppercase font-mono block"><?= h($apt['service_code']) ?></span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-bold text-slate-800"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span>
                <span class="text-[11px] text-slate-500 block"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
              </td>
              <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                <?= h($apt['assigned_personnel']) ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border <?= $statusBadge ?>">
                  <?= ucfirst(h($apt['status'])) ?>
                </span>
                <?php if ($apt['status'] === 'rescheduled' && $apt['previous_appointment_date']): ?>
                  <div class="text-[10px] text-amber-700 mt-0.5">Prev: <?= date('M d, Y', strtotime($apt['previous_appointment_date'])) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 max-w-xs text-slate-600">
                <?php if ($apt['reason']): ?>
                  <div><strong>Reason:</strong> <?= h($apt['reason']) ?></div>
                <?php endif; ?>
                <?php if ($apt['clinical_notes']): ?>
                  <div class="mt-1 text-emerald-700"><strong>Notes:</strong> <?= h($apt['clinical_notes']) ?></div>
                <?php endif; ?>
                <?php if ($apt['cancellation_reason']): ?>
                  <div class="mt-1 text-rose-700"><strong>Cancelled:</strong> <?= h($apt['cancellation_reason']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
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
