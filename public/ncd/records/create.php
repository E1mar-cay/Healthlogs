<?php
$pageTitle = 'Enroll Patient to NCD Program';
require __DIR__ . '/../../partials/bootstrap.php';

$errors = [];
$patients = [];

try {
    $patients = $pdo->query("
        SELECT id, first_name, last_name, middle_name, birth_date, barangay, sex, contact_no 
        FROM patients 
        WHERE status = 'active'
        ORDER BY last_name ASC, first_name ASC
    ")->fetchAll();
} catch (Throwable $e) {}

// Auto-generate next NCD code
$currYear = date('Y');
$nextCode = 'NCD-' . $currYear . '-0001';
try {
    $lastCode = $pdo->query("SELECT ncd_code FROM ncd_records WHERE ncd_code LIKE 'NCD-{$currYear}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($lastCode && preg_match('/NCD-\d{4}-(\d+)/', $lastCode, $m)) {
        $seq = (int)$m[1] + 1;
        $nextCode = sprintf('NCD-%s-%04d', $currYear, $seq);
    }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $ncd_code = trim($_POST['ncd_code'] ?? $nextCode);
    $registration_date = trim($_POST['registration_date'] ?? date('Y-m-d'));
    $diagnosis_type = trim($_POST['diagnosis_type'] ?? 'hypertension');
    $date_diagnosed = trim($_POST['date_diagnosed'] ?? date('Y-m-d'));
    $philpen_risk_level = trim($_POST['philpen_risk_level'] ?? 'low');
    $is_smoker = trim($_POST['is_smoker'] ?? 'never');
    $is_alcohol_drinker = trim($_POST['is_alcohol_drinker'] ?? 'never');
    $maintenance_meds = trim($_POST['maintenance_meds'] ?? '');
    $target_bp = trim($_POST['target_bp'] ?? $_POST['actual_bp'] ?? '');
    $target_fbs = trim($_POST['target_fbs'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($patient_id <= 0) {
        $errors[] = 'Please select a patient to enroll.';
    }
    if (empty($ncd_code)) {
        $errors[] = 'NCD code is required.';
    }
    if (empty($registration_date)) {
        $errors[] = 'Registration date is required.';
    }
    if (empty($date_diagnosed)) {
        $errors[] = 'Date diagnosed is required.';
    }

    // Check duplicate active record
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id, ncd_code FROM ncd_records WHERE patient_id = ? AND status IN ('active', 'controlled', 'uncontrolled')");
        $chk->execute([$patient_id]);
        if ($ex = $chk->fetch()) {
            $errors[] = "This patient already has an active NCD registry profile ({$ex['ncd_code']}).";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ncd_records (
                    patient_id, ncd_code, registration_date, diagnosis_type, date_diagnosed,
                    philpen_risk_level, is_smoker, is_alcohol_drinker, maintenance_meds,
                    target_bp, target_fbs, status, remarks
                ) VALUES (
                    :patient_id, :ncd_code, :registration_date, :diagnosis_type, :date_diagnosed,
                    :philpen_risk_level, :is_smoker, :is_alcohol_drinker, :maintenance_meds,
                    :target_bp, :target_fbs, :status, :remarks
                )
            ");
            $stmt->execute([
                'patient_id' => $patient_id,
                'ncd_code' => $ncd_code,
                'registration_date' => $registration_date,
                'diagnosis_type' => $diagnosis_type,
                'date_diagnosed' => $date_diagnosed,
                'philpen_risk_level' => $philpen_risk_level,
                'is_smoker' => $is_smoker,
                'is_alcohol_drinker' => $is_alcohol_drinker,
                'maintenance_meds' => $maintenance_meds ?: null,
                'target_bp' => $target_bp ?: null,
                'target_fbs' => $target_fbs ?: null,
                'status' => $status,
                'remarks' => $remarks ?: null,
            ]);

            $newRecordId = (int)$pdo->lastInsertId();

            // If an actual BP or actual FBS was provided upon enrollment, automatically create initial baseline visit
            $sys = null;
            $dia = null;
            $bsVal = null;
            $bsType = 'none';

            if (!empty($target_bp) && preg_match('/^(\d{2,3})\s*[\/\-]\s*(\d{2,3})$/', $target_bp, $bpMatches)) {
                $sys = (int)$bpMatches[1];
                $dia = (int)$bpMatches[2];
            }
            if (!empty($target_fbs) && preg_match('/(\d+(?:\.\d+)?)/', $target_fbs, $fbsMatches)) {
                $bsVal = (float)$fbsMatches[1];
                $bsType = 'fbs';
            }

            if ($sys !== null || $bsVal !== null) {
                try {
                    $vStmt = $pdo->prepare("
                        INSERT INTO ncd_visits (
                            ncd_record_id, visit_date, bp_systolic, bp_diastolic, blood_sugar_mgdl, blood_sugar_type,
                            treatment_adherence, findings_complaints, management_plan, recorded_by
                        ) VALUES (
                            :ncd_record_id, :visit_date, :bp_systolic, :bp_diastolic, :blood_sugar_mgdl, :blood_sugar_type,
                            'good', 'Baseline clinical vitals recorded upon program enrollment.', 'Initial clinical enrollment & PhilPEN risk assessment.', :recorded_by
                        )
                    ");
                    $vStmt->execute([
                        'ncd_record_id' => $newRecordId,
                        'visit_date' => $registration_date,
                        'bp_systolic' => $sys,
                        'bp_diastolic' => $dia,
                        'blood_sugar_mgdl' => $bsVal,
                        'blood_sugar_type' => $bsType,
                        'recorded_by' => function_exists('current_user_id') ? current_user_id() : null,
                    ]);
                } catch (Throwable $ve) {
                    // Non-critical, continue
                }
            }

            set_flash('success', "NCD Client [{$ncd_code}] successfully enrolled!");
            header("Location: /HealthLogs/public/ncd/visits/index.php?record_id={$newRecordId}");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to enroll client: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../partials/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
  <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500 font-semibold">
          <a href="/HealthLogs/public/ncd/records/index.php" class="text-slate-500 hover:text-slate-800 hover:underline">&larr; Back to NCD Registry</a>
        </div>
        <div class="text-2xl font-bold text-slate-900 mt-1">Enroll Patient into NCD Program</div>
        <p class="text-sm text-slate-500 mt-1">PhilPEN assessment and chronic lifestyle disease registry enrollment.</p>
      </div>
      <span class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center text-xl font-bold">
        <i class="fas fa-heart-pulse"></i>
      </span>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 text-sm">
      <div class="font-bold mb-1 flex items-center gap-1.5"><i class="fas fa-circle-exclamation"></i> Please resolve the following errors:</div>
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $err): ?>
          <li><?= h($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl shadow p-5 sm:p-8 space-y-6">
    <!-- Section 1: Patient Identity -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-id-card text-slate-500"></i> 1. Patient Profile
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Patient *</label>
          <select name="patient_id" required data-searchable-select data-search-placeholder="Search patient name or barangay..." class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
            <option value="">-- Choose Patient --</option>
            <?php foreach ($patients as $p): ?>
              <?php 
                $age = $p['birth_date'] ? (date('Y') - date('Y', strtotime($p['birth_date']))) : '';
                $sel = (isset($_POST['patient_id']) && (int)$_POST['patient_id'] === (int)$p['id']) ? 'selected' : '';
              ?>
              <option value="<?= (int)$p['id'] ?>" <?= $sel ?>>
                <?= h($p['last_name'] . ', ' . $p['first_name'] . ' (' . ($p['sex'] === 'male' ? 'M' : 'F') . ', ' . $age . 'yo) - Brgy. ' . $p['barangay']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">NCD Tracking Code *</label>
          <input type="text" name="ncd_code" value="<?= h($_POST['ncd_code'] ?? $nextCode) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Date *</label>
          <input type="date" name="registration_date" value="<?= h($_POST['registration_date'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
        </div>
      </div>
    </div>

    <!-- Section 2: Clinical Details -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-stethoscope text-slate-500"></i> 2. Clinical Diagnosis &amp; PhilPEN Assessment
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Diagnosis *</label>
          <select name="diagnosis_type" id="createDiagnosisType" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="hypertension" <?= (($_POST['diagnosis_type'] ?? '') === 'hypertension') ? 'selected' : '' ?>>Hypertension (HPN)</option>
            <option value="diabetes" <?= (($_POST['diagnosis_type'] ?? '') === 'diabetes') ? 'selected' : '' ?>>Type 2 Diabetes Mellitus (DM)</option>
            <option value="hypertension_diabetes" <?= (($_POST['diagnosis_type'] ?? '') === 'hypertension_diabetes') ? 'selected' : '' ?>>Hypertension &amp; Type 2 Diabetes</option>
            <option value="cardiovascular" <?= (($_POST['diagnosis_type'] ?? '') === 'cardiovascular') ? 'selected' : '' ?>>Cardiovascular Disease (CVD / CAD)</option>
            <option value="asthma_copd" <?= (($_POST['diagnosis_type'] ?? '') === 'asthma_copd') ? 'selected' : '' ?>>Bronchial Asthma / COPD</option>
            <option value="chronic_kidney" <?= (($_POST['diagnosis_type'] ?? '') === 'chronic_kidney') ? 'selected' : '' ?>>Chronic Kidney Disease (CKD)</option>
            <option value="cancer" <?= (($_POST['diagnosis_type'] ?? '') === 'cancer') ? 'selected' : '' ?>>Cancer Registry</option>
            <option value="other" <?= (($_POST['diagnosis_type'] ?? '') === 'other') ? 'selected' : '' ?>>Other Chronic Condition</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Date Diagnosed *</label>
          <input type="date" name="date_diagnosed" value="<?= h($_POST['date_diagnosed'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">PhilPEN CVD Risk Level *</label>
            <span id="createRiskPercentageBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">&lt;10% (Low)</span>
          </div>
          <select name="philpen_risk_level" id="createPhilpenRiskSelect" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="low" <?= (($_POST['philpen_risk_level'] ?? '') === 'low') ? 'selected' : '' ?>>Low Risk (&lt;10%)</option>
            <option value="moderate" <?= (($_POST['philpen_risk_level'] ?? '') === 'moderate') ? 'selected' : '' ?>>Moderate Risk (10% to &lt;20%)</option>
            <option value="high" <?= (($_POST['philpen_risk_level'] ?? '') === 'high') ? 'selected' : '' ?>>High Risk (20% to &lt;30%)</option>
            <option value="very_high" <?= (($_POST['philpen_risk_level'] ?? '') === 'very_high') ? 'selected' : '' ?>>Very High Risk (&ge;30%)</option>
          </select>
          <div id="createRiskExplanation" class="text-[10px] text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fas fa-heart-pulse text-indigo-500 text-[10px]"></i>
            <span id="createRiskExplanationText">Auto-calculated based on BP &amp; Blood Sugar</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Program Status</label>
          <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active (Under Monitoring)</option>
            <option value="controlled" <?= (($_POST['status'] ?? '') === 'controlled') ? 'selected' : '' ?>>Controlled (Target Achieved)</option>
            <option value="uncontrolled" <?= (($_POST['status'] ?? '') === 'uncontrolled') ? 'selected' : '' ?>>Uncontrolled / High Alert</option>
            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <!-- Disease Risk Profile & Recommended Medicine Banner -->
        <div class="sm:col-span-2 p-3.5 rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/90 via-sky-50/50 to-blue-50/70 shadow-xs" id="createDiseaseGuideCard">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100 pb-2.5">
            <div class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                <i class="fas fa-notes-medical"></i>
              </span>
              <div>
                <div class="text-[10px] uppercase font-bold text-indigo-700 tracking-wider">Disease Risk Profile</div>
                <div class="text-xs font-bold text-slate-900" id="createDiseaseTitle">Hypertension (HPN)</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-[11px] text-slate-500 font-medium">Risk of Disease:</span>
              <span id="createDiseaseRiskBadge" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                10% to &lt;20% (Moderate Risk)
              </span>
            </div>
          </div>

          <!-- Medicine to Give -->
          <div class="mt-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-start gap-1.5 text-slate-800">
              <i class="fas fa-pills text-indigo-600 mt-0.5 shrink-0"></i>
              <div>
                <span class="font-bold text-indigo-900">Recommended Medicine to Give:</span>
                <span id="createDiseaseMedsText" class="font-semibold text-slate-900 ml-1">Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD</span>
                <div class="text-[10px] text-slate-500 mt-0.5" id="createDiseaseNotes">DOH First-line Antihypertensive Protocol for Adults.</div>
              </div>
            </div>
            <button type="button" id="createApplyMedsBtn" class="shrink-0 text-[11px] font-bold text-indigo-700 bg-white hover:bg-indigo-50 active:scale-95 border border-indigo-300 px-3 py-1.5 rounded-lg transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
              <i class="fas fa-prescription-bottle-alt"></i> Apply Medicine
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Lifestyle & Treatment -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-pills text-slate-500"></i> 3. Lifestyle Factors &amp; Maintenance Regimen
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Smoking History</label>
          <select name="is_smoker" id="createIsSmoker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= (($_POST['is_smoker'] ?? '') === 'never') ? 'selected' : '' ?>>Never Smoked</option>
            <option value="former" <?= (($_POST['is_smoker'] ?? '') === 'former') ? 'selected' : '' ?>>Former Smoker (Quit)</option>
            <option value="current" <?= (($_POST['is_smoker'] ?? '') === 'current') ? 'selected' : '' ?>>Current Smoker</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alcohol History</label>
          <select name="is_alcohol_drinker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'never') ? 'selected' : '' ?>>Non-Drinker</option>
            <option value="occasional" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'occasional') ? 'selected' : '' ?>>Occasional Drinker</option>
            <option value="regular" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'regular') ? 'selected' : '' ?>>Regular Drinker</option>
            <option value="heavy" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'heavy') ? 'selected' : '' ?>>Heavy Binge Drinker</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Maintenance Medications</label>
            <span class="text-[10px] text-slate-400">Click a chip below to insert medicine</span>
          </div>
          <input type="text" name="maintenance_meds" id="createMaintenanceMeds" value="<?= h($_POST['maintenance_meds'] ?? '') ?>" placeholder="e.g. Losartan 50mg 1 tab OD, Metformin 500mg 1 tab BID" class="w-full border rounded-lg px-3 py-2 text-sm" />
          <div class="mt-1.5 flex flex-wrap items-center gap-1.5" id="createMedsChipContainer"></div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Pressure / BP (mmHg)</label>
          <input type="text" name="target_bp" id="createTargetBp" value="<?= h($_POST['target_bp'] ?? '') ?>" placeholder="e.g. 120/80" class="w-full border rounded-lg px-3 py-2 text-sm" />
          <span class="text-[10px] text-slate-400">Actual BP upon enrollment</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Sugar / FBS (mg/dL)</label>
          <input type="text" name="target_fbs" id="createTargetFbs" value="<?= h($_POST['target_fbs'] ?? '') ?>" placeholder="e.g. 100" class="w-full border rounded-lg px-3 py-2 text-sm" />
          <span class="text-[10px] text-slate-400">Actual FBS upon enrollment</span>
        </div>

        <!-- Live PhilPEN CVD Risk Preview -->
        <div class="sm:col-span-2 p-3 rounded-xl border bg-slate-50 border-slate-200 flex items-center justify-between" id="createVitalsRiskBanner">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
              <i class="fas fa-calculator"></i>
            </div>
            <div>
              <div class="text-[10px] uppercase font-bold text-slate-400">Calculated PhilPEN CVD Risk Level</div>
              <div class="text-xs font-semibold text-slate-800" id="createVitalsRiskSummary">Enter BP &amp; FBS above to assess CVD risk</div>
            </div>
          </div>
          <span id="createVitalsRiskBadge" class="text-xs font-bold px-3 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
            &lt;10% Risk
          </span>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Remarks / Diet Guidance</label>
          <textarea name="remarks" rows="3" placeholder="Additional clinical notes, dietary instructions, or allergies..." class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($_POST['remarks'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
      <a href="/HealthLogs/public/ncd/records/index.php" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow transition inline-flex items-center gap-1.5">
        <i class="fas fa-check"></i> Complete Enrollment
      </button>
    </div>
  </form>
</div>

<script>
(function initCreateRiskCalculator() {
  const NCD_PROTOCOLS = {
    'hypertension': {
      title: 'Hypertension (HPN)',
      baseRiskLevel: 'moderate',
      riskPercentage: '10% to <20%',
      riskBadgeClass: 'bg-amber-100 text-amber-800 border-amber-200',
      meds: 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD',
      medsChips: ['Losartan 50mg OD', 'Amlodipine 5mg OD', 'Metoprolol 50mg OD'],
      notes: 'DOH First-line Antihypertensive Protocol for Adults.'
    },
    'diabetes': {
      title: 'Type 2 Diabetes Mellitus (DM)',
      baseRiskLevel: 'moderate',
      riskPercentage: '10% to <20%',
      riskBadgeClass: 'bg-amber-100 text-amber-800 border-amber-200',
      meds: 'Metformin 500mg 1 tab BID after meals',
      medsChips: ['Metformin 500mg BID', 'Gliclazide 80mg OD'],
      notes: 'First-line Biguanide oral hypoglycemic regimen.'
    },
    'hypertension_diabetes': {
      title: 'Hypertension & Type 2 Diabetes',
      baseRiskLevel: 'high',
      riskPercentage: '20% to <30%',
      riskBadgeClass: 'bg-orange-100 text-orange-800 border-orange-200',
      meds: 'Losartan 50mg OD, Metformin 500mg BID, Amlodipine 5mg OD',
      medsChips: ['Losartan 50mg OD', 'Metformin 500mg BID', 'Amlodipine 5mg OD'],
      notes: 'Dual cardiovascular-metabolic regimen with organ-protective ARB.'
    },
    'cardiovascular': {
      title: 'Cardiovascular Disease (CVD / CAD / Post-Stroke)',
      baseRiskLevel: 'very_high',
      riskPercentage: '≥30%',
      riskBadgeClass: 'bg-rose-100 text-rose-800 border-rose-200',
      meds: 'Aspirin 80mg OD, Atorvastatin 20mg OD, Carvedilol 6.25mg BID',
      medsChips: ['Aspirin 80mg OD', 'Atorvastatin 20mg OD', 'Carvedilol 6.25mg BID'],
      notes: 'Secondary prevention: Antiplatelet, high-potency Statin & Beta-blocker.'
    },
    'asthma_copd': {
      title: 'Bronchial Asthma / COPD',
      baseRiskLevel: 'moderate',
      riskPercentage: '10% to <20%',
      riskBadgeClass: 'bg-amber-100 text-amber-800 border-amber-200',
      meds: 'Salbutamol 100mcg MDI 2 puffs PRN, Montelukast 10mg 1 tab OD',
      medsChips: ['Salbutamol 100mcg MDI PRN', 'Montelukast 10mg OD'],
      notes: 'Short-acting Beta-2 agonist rescue + Leukotriene receptor antagonist.'
    },
    'chronic_kidney': {
      title: 'Chronic Kidney Disease (CKD)',
      baseRiskLevel: 'very_high',
      riskPercentage: '≥30%',
      riskBadgeClass: 'bg-rose-100 text-rose-800 border-rose-200',
      meds: 'Losartan 25mg OD, Sodium Bicarbonate 650mg TID, Furosemide 20mg OD',
      medsChips: ['Losartan 25mg OD', 'Sodium Bicarbonate 650mg TID', 'Furosemide 20mg OD'],
      notes: 'Renoprotective dosing, metabolic acidosis correction & volume control.'
    },
    'cancer': {
      title: 'Cancer Registry',
      baseRiskLevel: 'high',
      riskPercentage: '20% to <30%',
      riskBadgeClass: 'bg-orange-100 text-orange-800 border-orange-200',
      meds: 'Prescribed Oncologic Maintenance Regimen, Multivitamins 1 tab OD',
      medsChips: ['Oncology Maintenance Regimen', 'Multivitamins OD'],
      notes: 'Supportive palliative / maintenance care per oncologist protocol.'
    },
    'other': {
      title: 'Other Chronic Condition',
      baseRiskLevel: 'moderate',
      riskPercentage: '10% to <20%',
      riskBadgeClass: 'bg-amber-100 text-amber-800 border-amber-200',
      meds: 'Maintenance regimen as evaluated by Municipal Health Officer',
      medsChips: ['Physician-Prescribed Regimen'],
      notes: 'Specialized chronic care per attending physician.'
    }
  };

  let lastAutoMeds = '';

  function updateDiseaseGuidance(diagVal) {
    const proto = NCD_PROTOCOLS[diagVal] || NCD_PROTOCOLS['hypertension'];

    const titleEl = document.getElementById('createDiseaseTitle');
    const badgeEl = document.getElementById('createDiseaseRiskBadge');
    const medsTextEl = document.getElementById('createDiseaseMedsText');
    const notesEl = document.getElementById('createDiseaseNotes');
    const medsInput = document.getElementById('createMaintenanceMeds');
    const chipContainer = document.getElementById('createMedsChipContainer');

    if (titleEl) titleEl.textContent = proto.title;
    if (badgeEl) {
      badgeEl.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full border ' + proto.riskBadgeClass;
      badgeEl.textContent = proto.riskPercentage + ' (' + proto.baseRiskLevel.replace('_', ' ').toUpperCase() + ' RISK)';
    }
    if (medsTextEl) medsTextEl.textContent = proto.meds;
    if (notesEl) notesEl.textContent = proto.notes;

    // Auto-fill meds if empty or matches previously auto-filled value
    if (medsInput && (!medsInput.value.trim() || medsInput.value.trim() === lastAutoMeds)) {
      medsInput.value = proto.meds;
      lastAutoMeds = proto.meds;
    }

    // Render clickable suggestion chips
    if (chipContainer) {
      chipContainer.innerHTML = '<span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><i class="fas fa-pills text-indigo-500 mr-1"></i>Rx Quick Add:</span>';
      proto.medsChips.forEach(chip => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'text-[11px] font-medium bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-800 border border-slate-200 px-2 py-0.5 rounded-md transition cursor-pointer';
        btn.textContent = '+ ' + chip;
        btn.onclick = function() {
          if (!medsInput) return;
          if (!medsInput.value.trim()) {
            medsInput.value = chip;
          } else if (!medsInput.value.includes(chip)) {
            medsInput.value = medsInput.value.trim() + ', ' + chip;
          }
        };
        chipContainer.appendChild(btn);
      });
    }
  }

  function recalculateRisk() {
    const bpInput = document.getElementById('createTargetBp');
    const fbsInput = document.getElementById('createTargetFbs');
    const smokerSelect = document.getElementById('createIsSmoker');
    const diagSelect = document.getElementById('createDiagnosisType');
    const riskSelect = document.getElementById('createPhilpenRiskSelect');
    const badge = document.getElementById('createRiskPercentageBadge');
    const explainText = document.getElementById('createRiskExplanationText');
    const bannerSummary = document.getElementById('createVitalsRiskSummary');
    const bannerBadge = document.getElementById('createVitalsRiskBadge');

    if (!riskSelect) return;

    const bpVal = bpInput ? bpInput.value.trim() : '';
    const fbsVal = fbsInput ? fbsInput.value.trim() : '';
    const smokerVal = smokerSelect ? smokerSelect.value : 'never';
    const diagVal = diagSelect ? diagSelect.value : 'hypertension';

    const proto = NCD_PROTOCOLS[diagVal] || NCD_PROTOCOLS['hypertension'];

    let sys = null, dia = null;
    if (bpVal) {
      const m = bpVal.match(/(\d{2,3})\s*[\/\-]\s*(\d{2,3})/);
      if (m) {
        sys = parseInt(m[1], 10);
        dia = parseInt(m[2], 10);
      }
    }

    let fbs = null;
    if (fbsVal) {
      const m = fbsVal.match(/(\d+(?:\.\d+)?)/);
      if (m) fbs = parseFloat(m[1]);
    }

    const isDiabetic = (fbs !== null && fbs >= 126) || diagVal === 'diabetes' || diagVal === 'hypertension_diabetes';
    const isSmoker = smokerVal === 'current';
    const isSevereFbs = (fbs !== null && fbs >= 200);

    const isCrisisBp = (sys !== null && sys >= 180) || (dia !== null && dia >= 110);
    const isStage2Bp = (sys !== null && sys >= 160) || (dia !== null && dia >= 100);
    const isStage1Bp = (sys !== null && sys >= 140) || (dia !== null && dia >= 90);
    const isElevatedBp = (sys !== null && sys >= 130) || (dia !== null && dia >= 85);

    // Compute risk rank: 1=low, 2=moderate, 3=high, 4=very_high
    const rankMap = { 'low': 1, 'moderate': 2, 'high': 3, 'very_high': 4 };
    let diseaseRank = rankMap[proto.baseRiskLevel] || 2;
    let vitalsRank = 1;

    let vitalsReason = '';
    if (isCrisisBp || (isStage2Bp && isDiabetic) || (isStage1Bp && isSevereFbs)) {
      vitalsRank = 4;
      vitalsReason = isCrisisBp 
        ? 'Crisis BP (≥180/110 mmHg)' 
        : 'Stage 2 BP with Diabetes';
    } else if (isStage2Bp || (isStage1Bp && isDiabetic) || isSevereFbs || (isStage1Bp && isSmoker)) {
      vitalsRank = 3;
      vitalsReason = isStage2Bp 
        ? 'Stage 2 HTN (≥160/100 mmHg)' 
        : (isStage1Bp && isDiabetic ? 'HTN with Diabetes' : 'Elevated vitals profile');
    } else if (isStage1Bp || isDiabetic || (isElevatedBp && isSmoker) || (fbs !== null && fbs >= 100 && isElevatedBp)) {
      vitalsRank = 2;
      vitalsReason = isStage1Bp 
        ? 'Stage 1 HTN (≥140/90 mmHg)' 
        : (isDiabetic ? 'Elevated FBS/Diabetes' : 'Pre-hypertension with metabolic factors');
    } else {
      vitalsRank = 1;
      vitalsReason = (sys !== null || fbs !== null) 
        ? 'Normal measured vitals' 
        : 'Vitals not fully specified';
    }

    const finalRank = Math.max(diseaseRank, vitalsRank);
    const invRank = { 1: 'low', 2: 'moderate', 3: 'high', 4: 'very_high' };
    const level = invRank[finalRank];

    const levelDetails = {
      'low': {
        pct: '<10%',
        pill: '<10% Risk (Low)',
        color: 'bg-emerald-100 text-emerald-800 border-emerald-200'
      },
      'moderate': {
        pct: '10% to <20%',
        pill: '10% to <20% Risk (Moderate)',
        color: 'bg-amber-100 text-amber-800 border-amber-200'
      },
      'high': {
        pct: '20% to <30%',
        pill: '20% to <30% Risk (High)',
        color: 'bg-orange-100 text-orange-800 border-orange-200'
      },
      'very_high': {
        pct: '≥30%',
        pill: '≥30% Risk (Very High)',
        color: 'bg-rose-100 text-rose-800 border-rose-200'
      }
    };

    const details = levelDetails[level];

    riskSelect.value = level;

    if (badge) {
      badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full border ' + details.color;
      badge.textContent = details.pct + ' (' + level.replace('_', ' ').toUpperCase() + ')';
    }

    if (explainText) {
      let parts = [];
      if (sys && dia) parts.push('BP ' + sys + '/' + dia);
      if (fbs) parts.push('FBS ' + fbs + ' mg/dL');
      let exp = 'Disease baseline: ' + proto.riskPercentage + ' (' + proto.title + ')';
      if (vitalsRank > diseaseRank && vitalsReason) {
        exp += ' escalated to ' + details.pct + ' via ' + vitalsReason;
      }
      explainText.textContent = exp;
    }

    if (bannerSummary) {
      if (vitalsRank > diseaseRank && vitalsReason) {
        bannerSummary.textContent = vitalsReason + ' elevates CVD risk to ' + details.pct;
      } else {
        bannerSummary.textContent = proto.title + ' standard risk profile (' + details.pct + ' CVD risk)';
      }
    }
    if (bannerBadge) {
      bannerBadge.className = 'text-xs font-bold px-3 py-1 rounded-lg border shrink-0 ' + details.color;
      bannerBadge.textContent = details.pill;
    }
  }

  const bpEl = document.getElementById('createTargetBp');
  const fbsEl = document.getElementById('createTargetFbs');
  const smokerEl = document.getElementById('createIsSmoker');
  const diagEl = document.getElementById('createDiagnosisType');
  const riskSelectEl = document.getElementById('createPhilpenRiskSelect');
  const applyBtn = document.getElementById('createApplyMedsBtn');

  if (diagEl) {
    diagEl.addEventListener('change', function() {
      updateDiseaseGuidance(this.value);
      recalculateRisk();
    });
  }

  if (applyBtn) {
    applyBtn.addEventListener('click', function() {
      const diagVal = diagEl ? diagEl.value : 'hypertension';
      const proto = NCD_PROTOCOLS[diagVal] || NCD_PROTOCOLS['hypertension'];
      const medsInput = document.getElementById('createMaintenanceMeds');
      if (medsInput) {
        medsInput.value = proto.meds;
        lastAutoMeds = proto.meds;
        medsInput.focus();
      }
    });
  }

  if (bpEl) bpEl.addEventListener('input', recalculateRisk);
  if (fbsEl) fbsEl.addEventListener('input', recalculateRisk);
  if (smokerEl) smokerEl.addEventListener('change', recalculateRisk);

  if (riskSelectEl) {
    riskSelectEl.addEventListener('change', function() {
      const map = {
        'low': { pct: '<10%', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        'moderate': { pct: '10% to <20%', color: 'bg-amber-100 text-amber-800 border-amber-200' },
        'high': { pct: '20% to <30%', color: 'bg-orange-100 text-orange-800 border-orange-200' },
        'very_high': { pct: '≥30%', color: 'bg-rose-100 text-rose-800 border-rose-200' }
      };
      const item = map[this.value] || map.low;
      const badge = document.getElementById('createRiskPercentageBadge');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full border ' + item.color;
        badge.textContent = item.pct;
      }
      const bannerBadge = document.getElementById('createVitalsRiskBadge');
      if (bannerBadge) {
        bannerBadge.className = 'text-xs font-bold px-3 py-1 rounded-lg border shrink-0 ' + item.color;
        bannerBadge.textContent = item.pct + ' (' + this.value.toUpperCase() + ')';
      }
    });
  }

  // Initial populate
  const initialDiag = diagEl ? diagEl.value : 'hypertension';
  updateDiseaseGuidance(initialDiag);
  recalculateRisk();
})();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
