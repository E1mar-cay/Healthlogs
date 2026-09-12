<?php
$pageTitle = 'NCD Consultations & Vital Logs';
require __DIR__ . '/../../partials/bootstrap.php';

$recordId = (int)($_GET['record_id'] ?? 0);
$successMsg = '';
$errorMsg = '';

// Handle Logging New Consultation Visit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ncd_visit') {
    $ncd_record_id = (int)($_POST['ncd_record_id'] ?? 0);
    $visit_date = trim($_POST['visit_date'] ?? date('Y-m-d'));
    $bp_systolic = !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null;
    $bp_diastolic = !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null;
    $blood_sugar_mgdl = !empty($_POST['blood_sugar_mgdl']) ? (float)$_POST['blood_sugar_mgdl'] : null;
    $blood_sugar_type = trim($_POST['blood_sugar_type'] ?? 'none');
    $weight_kg = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
    $height_cm = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null;
    $bmi = null;
    if ($weight_kg && $height_cm && $height_cm > 0) {
        $bmi = round($weight_kg / (($height_cm / 100) * ($height_cm / 100)), 1);
    } elseif (!empty($_POST['bmi'])) {
        $bmi = (float)$_POST['bmi'];
    }
    $waist_cm = !empty($_POST['waist_cm']) ? (float)$_POST['waist_cm'] : null;
    $medications_dispensed = trim($_POST['medications_dispensed'] ?? '');
    $quantity_dispensed = (int)($_POST['quantity_dispensed'] ?? 0);
    $treatment_adherence = trim($_POST['treatment_adherence'] ?? 'good');
    $findings_complaints = trim($_POST['findings_complaints'] ?? '');
    $management_plan = trim($_POST['management_plan'] ?? '');
    $next_appointment_date = trim($_POST['next_appointment_date'] ?? '');
    $recorded_by = $_SESSION['user_id'] ?? null;

    if ($ncd_record_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $visit_date)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ncd_visits (
                    ncd_record_id, visit_date, bp_systolic, bp_diastolic,
                    blood_sugar_mgdl, blood_sugar_type, weight_kg, height_cm, bmi, waist_cm,
                    medications_dispensed, quantity_dispensed, treatment_adherence,
                    findings_complaints, management_plan, next_appointment_date, recorded_by
                ) VALUES (
                    :ncd_record_id, :visit_date, :bp_systolic, :bp_diastolic,
                    :blood_sugar_mgdl, :blood_sugar_type, :weight_kg, :height_cm, :bmi, :waist_cm,
                    :medications_dispensed, :quantity_dispensed, :treatment_adherence,
                    :findings_complaints, :management_plan, :next_appointment_date, :recorded_by
                )
            ");
            $stmt->execute([
                'ncd_record_id' => $ncd_record_id,
                'visit_date' => $visit_date,
                'bp_systolic' => $bp_systolic,
                'bp_diastolic' => $bp_diastolic,
                'blood_sugar_mgdl' => $blood_sugar_mgdl,
                'blood_sugar_type' => ($blood_sugar_mgdl ? $blood_sugar_type : 'none'),
                'weight_kg' => $weight_kg,
                'height_cm' => $height_cm,
                'bmi' => $bmi,
                'waist_cm' => $waist_cm,
                'medications_dispensed' => $medications_dispensed ?: null,
                'quantity_dispensed' => $quantity_dispensed,
                'treatment_adherence' => $treatment_adherence,
                'findings_complaints' => $findings_complaints ?: 'Routine checkup & vitals log',
                'management_plan' => $management_plan ?: 'Continue maintenance regimen as prescribed',
                'next_appointment_date' => ($next_appointment_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment_date)) ? $next_appointment_date : null,
                'recorded_by' => $recorded_by,
            ]);

            // Auto-schedule reminder if next appointment date is provided
            if ($next_appointment_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment_date)) {
                $cStmt = $pdo->prepare("SELECT r.patient_id, r.ncd_code, r.diagnosis_type, p.contact_no FROM ncd_records r JOIN patients p ON p.id = r.patient_id WHERE r.id = ?");
                $cStmt->execute([$ncd_record_id]);
                $cl = $cStmt->fetch();
                if ($cl && !empty($cl['patient_id'])) {
                    $reminderMsg = "Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill is scheduled on " . date('M d, Y', strtotime($next_appointment_date)) . ". Please bring your monitoring card.";
                    $remStmt = $pdo->prepare("
                        INSERT INTO reminders (patient_id, reminder_type, due_date, message, status)
                        VALUES (?, 'non_communicable', ?, ?, 'pending')
                    ");
                    $remStmt->execute([$cl['patient_id'], $next_appointment_date, $reminderMsg]);
                }
            }

            $successMsg = "Checkup & vital signs logged successfully! Next follow-up scheduled for " . ($next_appointment_date ?: 'N/A') . ".";
            $recordId = $ncd_record_id;
        } catch (Throwable $e) {
            $errorMsg = 'Failed to save consultation log: ' . $e->getMessage();
        }
    } else {
        $errorMsg = 'Please select a valid NCD patient and visit date.';
    }
}

// Fetch active client details if record_id is passed
$selectedClient = null;
if ($recordId > 0) {
    $cStmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.middle_name, p.contact_no, p.barangay, p.sex, p.birth_date,
               TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age
        FROM ncd_records r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.id = ?
    ");
    $cStmt->execute([$recordId]);
    $selectedClient = $cStmt->fetch();
}

// Count and fetch visits with pagination
$vCountQuery = "
    SELECT COUNT(*)
    FROM ncd_visits v
    JOIN ncd_records r ON r.id = v.ncd_record_id
    JOIN patients p ON p.id = r.patient_id
";

$visitQuery = "
    SELECT v.*, r.ncd_code, r.diagnosis_type, r.philpen_risk_level, p.first_name, p.last_name, p.contact_no, p.barangay, u.full_name AS recorded_by_name
    FROM ncd_visits v
    JOIN ncd_records r ON r.id = v.ncd_record_id
    JOIN patients p ON p.id = r.patient_id
    LEFT JOIN users u ON u.id = v.recorded_by
";
$vWhere = "";
$vParams = [];

if ($recordId > 0) {
    $vWhere .= " WHERE v.ncd_record_id = :record_id";
    $vParams['record_id'] = $recordId;
}

// Count total visits
$vCountStmt = $pdo->prepare($vCountQuery . $vWhere);
$vCountStmt->execute($vParams);
$totalVisitsCount = (int)$vCountStmt->fetchColumn();

// Paginator (10 visits per page)
$visitPaginator = paginate($totalVisitsCount, 10);

$visitQuery .= $vWhere . " ORDER BY v.visit_date DESC, v.id DESC " . $visitPaginator->getLimitSql();
$vStmt = $pdo->prepare($visitQuery);
$vStmt->execute($vParams);
$visits = $vStmt->fetchAll();

// All active clients list for the visit logger dropdown
$activeClientsList = [];
try {
    $activeClientsList = $pdo->query("
        SELECT r.id, r.ncd_code, r.diagnosis_type, r.maintenance_meds, p.first_name, p.last_name, p.barangay
        FROM ncd_records r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.status IN ('active', 'controlled', 'uncontrolled')
        ORDER BY p.last_name ASC, p.first_name ASC
    ")->fetchAll();
} catch (Throwable $e) {}

function ncd_format_diag_name(?string $d): string {
    $map = [
        'hypertension' => 'Hypertension (HPN)',
        'diabetes' => 'Type 2 Diabetes (DM)',
        'hypertension_diabetes' => 'HPN & Diabetes',
        'cardiovascular' => 'Cardiovascular Disease',
        'asthma_copd' => 'Asthma / COPD',
        'chronic_kidney' => 'Chronic Kidney Disease',
        'cancer' => 'Cancer Registry',
        'other' => 'Other NCD',
    ];
    return $map[$d] ?? ucwords(str_replace('_', ' ', (string)$d));
}

require __DIR__ . '/../../partials/header.php';
?>

<!-- Header Banner -->
<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-semibold">
        <a href="/HealthLogs/public/ncd.php" class="text-slate-500 hover:text-slate-800 hover:underline">&larr; Back to NCD Dashboard</a>
      </div>
      <div class="text-2xl font-bold text-slate-900 mt-1">NCD Consultations &amp; Vital Signs Log</div>
      <p class="text-sm text-slate-500 mt-1">Record clinical checkups, BP measurements, blood sugar (FBS), maintenance refills, and scheduled follow-ups.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if ($recordId > 0): ?>
        <a href="/HealthLogs/public/ncd/visits/index.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
          <i class="fas fa-list mr-1.5 text-xs"></i> View All Patients
        </a>
      <?php endif; ?>
      <a href="/HealthLogs/public/ncd/records/index.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-users mr-1.5 text-xs"></i> Client Registry
      </a>
      <a href="/HealthLogs/public/ncd/tcl.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-table-list mr-1.5 text-xs"></i> TCL-NCD Report
      </a>
    </div>
  </div>
</div>

<?php if ($successMsg): ?>
  <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 text-sm mb-6 flex items-center gap-2 shadow-xs">
    <i class="fas fa-circle-check text-emerald-600 text-base"></i>
    <span><?= h($successMsg) ?></span>
  </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
  <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 text-sm mb-6 flex items-center gap-2 shadow-xs">
    <i class="fas fa-circle-exclamation text-rose-600 text-base"></i>
    <span><?= h($errorMsg) ?></span>
  </div>
<?php endif; ?>

<!-- Selected Patient Profile Banner (if filtered by record_id) -->
<?php if ($selectedClient): ?>
  <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 sm:p-6 text-white shadow-lg mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full bg-teal-500/20 text-teal-300 border border-teal-500/30 text-xs font-mono font-bold">
            <?= h($selectedClient['ncd_code']) ?>
          </span>
          <span class="text-xs uppercase tracking-wider text-slate-300 font-semibold">
            Status: <?= ucfirst($selectedClient['status']) ?>
          </span>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold mt-1 text-white">
          <?= h($selectedClient['last_name'] . ', ' . $selectedClient['first_name'] . ' ' . $selectedClient['middle_name']) ?>
        </h2>
        <div class="text-sm text-slate-300 mt-1 flex flex-wrap items-center gap-3">
          <span><i class="fas fa-cake-candles text-slate-400 mr-1"></i><?= $selectedClient['age'] ?> yrs old (<?= ucfirst($selectedClient['sex']) ?>)</span>
          <span><i class="fas fa-location-dot text-slate-400 mr-1"></i>Brgy. <?= h($selectedClient['barangay']) ?></span>
          <?php if (!empty($selectedClient['contact_no'])): ?>
            <span><i class="fas fa-phone text-slate-400 mr-1"></i><?= h($selectedClient['contact_no']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex sm:flex-col items-start sm:items-end justify-between border-t sm:border-t-0 border-slate-700/60 pt-3 sm:pt-0 gap-2">
        <div class="text-xs text-slate-300">
          Diagnosis: <strong class="text-teal-300"><?= ncd_format_diag_name($selectedClient['diagnosis_type']) ?></strong>
        </div>
        <div class="text-xs text-slate-300">
          PhilPEN Risk: <strong class="text-amber-300"><?= ucfirst($selectedClient['philpen_risk_level']) ?> Risk</strong>
        </div>
        <div class="text-xs text-slate-300 max-w-xs text-right truncate" title="<?= h($selectedClient['maintenance_meds']) ?>">
          Meds: <span class="text-slate-200"><?= h($selectedClient['maintenance_meds'] ?: 'None') ?></span>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- 1. FULL WIDTH FORM ON TOP: Log Checkup & Vitals -->
<div class="bg-white rounded-2xl shadow-md border border-slate-200 p-5 sm:p-7 mb-8">
  <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-200">
    <span class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-base shadow-xs">
      <i class="fas fa-notes-medical"></i>
    </span>
    <div>
      <h3 class="text-lg font-bold text-slate-900">Log Checkup &amp; Clinical Vitals</h3>
      <p class="text-xs text-slate-500">Record clinical measurements, BP, blood sugar (FBS/RBS), maintenance refills, and next appointment schedule.</p>
    </div>
  </div>

  <form method="POST" action="/HealthLogs/public/ncd/visits/index.php<?= $recordId > 0 ? '?record_id=' . $recordId : '' ?>" class="space-y-6">
    <input type="hidden" name="action" value="save_ncd_visit" />

    <!-- Group 1: Patient Selection & Clinical Vitals -->
    <div>
      <h4 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 pb-1.5">
        <i class="fas fa-heart-pulse text-indigo-600"></i> 1. Patient &amp; Clinical Measurements
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php if ($selectedClient): ?>
          <input type="hidden" name="ncd_record_id" value="<?= (int)$selectedClient['id'] ?>" />
          <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Selected Patient</label>
            <div class="p-2.5 rounded-lg bg-slate-100 text-xs font-bold text-slate-900 border border-slate-200 flex items-center justify-between">
              <span><?= h($selectedClient['ncd_code']) ?> &bull; <?= h($selectedClient['last_name'] . ', ' . $selectedClient['first_name']) ?> (<?= ncd_format_diag_name($selectedClient['diagnosis_type']) ?>)</span>
              <span class="text-slate-400 font-normal">Brgy. <?= h($selectedClient['barangay']) ?></span>
            </div>
          </div>
        <?php else: ?>
          <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Select Enrolled Patient *</label>
            <select name="ncd_record_id" id="ncdPatientSelect" required class="w-full border rounded-lg px-3 py-2 text-xs bg-white font-medium focus:ring-2 focus:ring-slate-400">
              <option value="">-- Choose Enrolled NCD Patient --</option>
              <?php foreach ($activeClientsList as $ac): ?>
                <option value="<?= (int)$ac['id'] ?>" <?= $recordId === (int)$ac['id'] ? 'selected' : '' ?> data-meds="<?= h($ac['maintenance_meds'] ?? '') ?>">
                  <?= h($ac['ncd_code'] . ' - ' . $ac['last_name'] . ', ' . $ac['first_name'] . ' (' . ncd_format_diag_name($ac['diagnosis_type']) . ') - Brgy. ' . $ac['barangay']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Consultation Date *</label>
          <input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Pressure (Systolic / Diastolic)</label>
          <div class="grid grid-cols-2 gap-2">
            <div class="relative">
              <input type="number" name="bp_systolic" min="60" max="260" placeholder="Systolic (120)" class="w-full border rounded-lg px-2.5 py-2 text-xs pr-10" />
              <span class="absolute right-2 top-2 text-[10px] text-slate-400 font-semibold">sys</span>
            </div>
            <div class="relative">
              <input type="number" name="bp_diastolic" min="40" max="160" placeholder="Diastolic (80)" class="w-full border rounded-lg px-2.5 py-2 text-xs pr-10" />
              <span class="absolute right-2 top-2 text-[10px] text-slate-400 font-semibold">dia</span>
            </div>
          </div>
        </div>

        <!-- Blood Sugar -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Sugar (mg/dL)</label>
          <div class="relative">
            <input type="number" step="0.1" name="blood_sugar_mgdl" placeholder="e.g. 110" class="w-full border rounded-lg px-3 py-2 text-xs pr-14" />
            <span class="absolute right-2.5 top-2 text-[10px] text-slate-400 font-semibold">mg/dL</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Sugar Type</label>
          <select name="blood_sugar_type" class="w-full border rounded-lg px-3 py-2 text-xs bg-white">
            <option value="fbs">FBS (Fasting Blood Sugar)</option>
            <option value="rbs">RBS (Random Blood Sugar)</option>
            <option value="hba1c">HbA1c (%)</option>
            <option value="none">None / Not Tested</option>
          </select>
        </div>

        <!-- Anthropometrics -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Weight &amp; Height</label>
          <div class="grid grid-cols-2 gap-2">
            <div class="relative">
              <input type="number" step="0.1" id="vWeight" name="weight_kg" placeholder="Weight (kg)" class="w-full border rounded-lg px-2.5 py-2 text-xs pr-7" oninput="calcBMI()" />
              <span class="absolute right-1.5 top-2 text-[10px] text-slate-400 font-semibold">kg</span>
            </div>
            <div class="relative">
              <input type="number" step="0.1" id="vHeight" name="height_cm" placeholder="Height (cm)" class="w-full border rounded-lg px-2.5 py-2 text-xs pr-7" oninput="calcBMI()" />
              <span class="absolute right-1.5 top-2 text-[10px] text-slate-400 font-semibold">cm</span>
            </div>
          </div>
        </div>

        <!-- Waist & BMI -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Waist Circ. &amp; Calc. BMI</label>
          <div class="grid grid-cols-2 gap-2">
            <div class="relative">
              <input type="number" step="0.1" name="waist_cm" placeholder="Waist" class="w-full border rounded-lg px-2.5 py-2 text-xs pr-7" />
              <span class="absolute right-1.5 top-2 text-[10px] text-slate-400 font-semibold">cm</span>
            </div>
            <div class="relative">
              <input type="number" step="0.1" id="vBmi" name="bmi" placeholder="BMI" readonly class="w-full border rounded-lg px-2.5 py-2 text-xs bg-slate-100 text-slate-700 font-bold" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Group 2: Prescriptions, Adherence & Clinical Notes -->
    <div>
      <h4 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 pb-1.5">
        <i class="fas fa-pills text-teal-600"></i> 2. Medications Dispensed &amp; Clinical Management
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Medications Dispensed / Refilled</label>
          <input type="text" id="vMedsDispensed" name="medications_dispensed" value="<?= h($selectedClient['maintenance_meds'] ?? '') ?>" placeholder="e.g. Losartan 50mg #30, Metformin 500mg #60" class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-slate-400" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Dispensed (Days/Units)</label>
          <input type="number" name="quantity_dispensed" value="30" min="0" placeholder="e.g. 30" class="w-full border rounded-lg px-3 py-2 text-xs" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Medication Adherence</label>
          <select name="treatment_adherence" class="w-full border rounded-lg px-3 py-2 text-xs bg-white font-medium">
            <option value="good">Good (Fully Compliant)</option>
            <option value="fair">Fair (Misses occasionally)</option>
            <option value="poor">Poor (Non-compliant / Skipped)</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Symptoms / Clinical Complaints</label>
          <textarea name="findings_complaints" rows="2" placeholder="e.g. Routine checkup, no headache, no shortness of breath, adherence counselled..." class="w-full border rounded-lg px-3 py-2 text-xs"></textarea>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Management Plan &amp; Lifestyle Advice</label>
          <textarea name="management_plan" rows="2" placeholder="e.g. Advised low salt low fat diet, daily 30-min brisk walking, maintain regular medications..." class="w-full border rounded-lg px-3 py-2 text-xs"></textarea>
        </div>
      </div>
    </div>

    <!-- Group 3: Follow-up Scheduling & Actions -->
    <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="w-full sm:w-80">
        <label class="block text-xs font-semibold text-slate-700 mb-1">
          <i class="fas fa-calendar-check text-slate-500 mr-1"></i> Next Checkup / Refill Date
        </label>
        <input type="date" name="next_appointment_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white" />
        <span class="text-[10px] text-slate-400 block mt-0.5">Automatically schedules an SMS follow-up reminder</span>
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
        <button type="submit" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs px-6 py-2.5 rounded-lg shadow transition inline-flex items-center justify-center gap-2">
          <i class="fas fa-check"></i> Save Consultation Record
        </button>
      </div>
    </div>
  </form>
</div>

<!-- 2. FULL WIDTH HISTORY SECTION BELOW: Consultation History & All Recent Logs -->
<div class="bg-white rounded-2xl shadow-md border border-slate-200 p-5 sm:p-7">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200 mb-5">
    <div>
      <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
        <i class="fas fa-clock-rotate-left text-slate-500"></i>
        <?= $selectedClient ? 'Consultation History for ' . h($selectedClient['first_name'] . ' ' . $selectedClient['last_name']) : 'All Recent NCD Consultations &amp; Vital Logs' ?>
      </h3>
      <p class="text-xs text-slate-500 mt-0.5">Master chronological record of vital signs, blood pressure readings, blood sugar logs, and issued medications.</p>
    </div>
    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-xs self-start sm:self-auto">
      <?= $totalVisitsCount ?> total session(s)
    </span>
  </div>

  <?php if (empty($visits)): ?>
    <div class="text-center py-16 text-slate-400">
      <i class="fas fa-heart-pulse text-4xl mb-3"></i>
      <div class="font-semibold text-slate-600">No consultation or vital logs found.</div>
      <p class="text-xs mt-1">Use the form above to log the patient's first clinical checkup.</p>
    </div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($visits as $v): ?>
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5 transition hover:bg-slate-100/80">
          <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-900 text-sm sm:text-base"><?= h($v['last_name'] . ', ' . $v['first_name']) ?></span>
                <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-semibold">
                  <?= h($v['ncd_code']) ?>
                </span>
                <span class="text-xs text-slate-600 font-medium">
                  &bull; <?= ncd_format_diag_name($v['diagnosis_type']) ?>
                </span>
                <span class="text-xs text-slate-400">
                  &bull; Brgy. <?= h($v['barangay']) ?>
                </span>
              </div>
              <div class="text-xs text-slate-500 mt-1">
                Consultation Date: <strong class="text-slate-800"><?= date('F d, Y', strtotime($v['visit_date'])) ?></strong>
                <?php if (!empty($v['recorded_by_name'])): ?>
                  &bull; Recorded by: <span class="text-slate-700"><?= h($v['recorded_by_name']) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="flex items-center gap-2 self-start">
              <?php if ($v['treatment_adherence'] === 'good'): ?>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">Adherence: Good</span>
              <?php elseif ($v['treatment_adherence'] === 'fair'): ?>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">Adherence: Fair</span>
              <?php else: ?>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">Adherence: Poor</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Vitals Metric Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-3.5 pt-3 border-t border-slate-200 text-xs">
            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
              <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Blood Pressure</div>
              <div class="font-extrabold text-sm sm:text-base mt-1">
                <?php if ($v['bp_systolic'] && $v['bp_diastolic']): ?>
                  <span class="<?= ($v['bp_systolic'] >= 140 || $v['bp_diastolic'] >= 90) ? 'text-rose-600' : 'text-emerald-700' ?>">
                    <?= $v['bp_systolic'] ?>/<?= $v['bp_diastolic'] ?> <span class="text-xs font-normal text-slate-400">mmHg</span>
                  </span>
                <?php else: ?>
                  <span class="text-slate-300 font-normal italic">—</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
              <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Blood Sugar</div>
              <div class="font-extrabold text-sm sm:text-base mt-1">
                <?php if ($v['blood_sugar_mgdl']): ?>
                  <span class="<?= $v['blood_sugar_mgdl'] >= 126 ? 'text-amber-600' : 'text-teal-700' ?>">
                    <?= number_format($v['blood_sugar_mgdl'], 1) ?> <span class="text-xs font-normal text-slate-400">mg/dL (<?= strtoupper($v['blood_sugar_type']) ?>)</span>
                  </span>
                <?php else: ?>
                  <span class="text-slate-300 font-normal italic">—</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
              <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Weight &amp; BMI</div>
              <div class="font-extrabold text-sm sm:text-base text-slate-900 mt-1">
                <?php if ($v['weight_kg']): ?>
                  <?= $v['weight_kg'] ?> <span class="text-xs font-normal text-slate-400">kg</span> <?= $v['bmi'] ? '<span class="text-xs font-semibold text-indigo-600 ml-1">(' . $v['bmi'] . ' BMI)</span>' : '' ?>
                <?php else: ?>
                  <span class="text-slate-300 font-normal italic">—</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
              <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Waist Circumference</div>
              <div class="font-extrabold text-sm sm:text-base text-slate-900 mt-1">
                <?= $v['waist_cm'] ? $v['waist_cm'] . ' <span class="text-xs font-normal text-slate-400">cm</span>' : '<span class="text-slate-300 font-normal italic">—</span>' ?>
              </div>
            </div>
          </div>

          <!-- Notes, Plan & Prescriptions -->
          <?php if (!empty($v['medications_dispensed']) || !empty($v['findings_complaints']) || !empty($v['management_plan'])): ?>
            <div class="space-y-1.5 text-xs bg-white p-3 rounded-xl border border-slate-200 text-slate-700 mt-2">
              <?php if (!empty($v['medications_dispensed'])): ?>
                <div>
                  <strong class="text-slate-900"><i class="fas fa-pills text-teal-600 mr-1.5"></i> Dispensed / Refilled:</strong> <?= h($v['medications_dispensed']) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($v['findings_complaints'])): ?>
                <div>
                  <strong class="text-slate-900"><i class="fas fa-notes-medical text-indigo-600 mr-1.5"></i> Symptoms &amp; Findings:</strong> <?= h($v['findings_complaints']) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($v['management_plan'])): ?>
                <div>
                  <strong class="text-slate-900"><i class="fas fa-clipboard-list text-slate-600 mr-1.5"></i> Management Plan:</strong> <?= h($v['management_plan']) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Next Appointment Footer -->
          <?php if (!empty($v['next_appointment_date'])): ?>
            <?php $isOverdue = ($v['next_appointment_date'] < date('Y-m-d')); ?>
            <div class="mt-3 text-xs flex items-center justify-between text-slate-500 pt-2 border-t border-slate-200/60">
              <span>
                Next Scheduled Follow-up: <strong class="<?= $isOverdue ? 'text-rose-600' : 'text-slate-800' ?>"><?= date('F d, Y', strtotime($v['next_appointment_date'])) ?></strong>
              </span>
              <?php if ($isOverdue): ?>
                <span class="text-rose-600 font-bold text-[11px] bg-rose-50 px-2 py-0.5 rounded border border-rose-200"><i class="fas fa-circle-exclamation mr-1"></i> Overdue</span>
              <?php else: ?>
                <span class="text-teal-700 font-semibold text-[11px] bg-teal-50 px-2 py-0.5 rounded border border-teal-200"><i class="fas fa-calendar-check mr-1"></i> Scheduled</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Pagination Controls -->
  <?php if (isset($visitPaginator)): ?>
    <?= $visitPaginator->render() ?>
  <?php endif; ?>
</div>

<script>
function calcBMI() {
  const w = parseFloat(document.getElementById('vWeight').value);
  const h = parseFloat(document.getElementById('vHeight').value);
  const bmiField = document.getElementById('vBmi');
  if (w > 0 && h > 0) {
    const bmiVal = (w / ((h / 100) * (h / 100))).toFixed(1);
    bmiField.value = bmiVal;
  } else {
    bmiField.value = '';
  }
}

// Auto-fill medications from selected dropdown patient
const selectPatient = document.getElementById('ncdPatientSelect');
if (selectPatient) {
  selectPatient.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const meds = selectedOption.getAttribute('data-meds');
    const medsField = document.getElementById('vMedsDispensed');
    if (meds && medsField && !medsField.value) {
      medsField.value = meds;
    }
  });
}
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
