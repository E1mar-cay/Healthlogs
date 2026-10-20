<?php
$pageTitle = 'Edit NCD Patient Profile';
require __DIR__ . '/../../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("
    SELECT r.*, p.first_name, p.last_name, p.middle_name, p.birth_date, p.barangay, p.sex, p.contact_no,
           TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age
    FROM ncd_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    set_flash('error', 'NCD record not found.');
    header('Location: /HealthLogs/public/ncd/records/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis_type = trim($_POST['diagnosis_type'] ?? $record['diagnosis_type']);
    $date_diagnosed = trim($_POST['date_diagnosed'] ?? $record['date_diagnosed']);
    $philpen_risk_level = trim($_POST['philpen_risk_level'] ?? $record['philpen_risk_level']);
    $is_smoker = trim($_POST['is_smoker'] ?? $record['is_smoker']);
    $is_alcohol_drinker = trim($_POST['is_alcohol_drinker'] ?? $record['is_alcohol_drinker']);
    $maintenance_meds = trim($_POST['maintenance_meds'] ?? '');
    $target_bp = trim($_POST['target_bp'] ?? $_POST['actual_bp'] ?? '');
    $target_fbs = trim($_POST['target_fbs'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($date_diagnosed)) {
        $errors[] = 'Date diagnosed is required.';
    }

    if (empty($errors)) {
        try {
            $uStmt = $pdo->prepare("
                UPDATE ncd_records SET
                    diagnosis_type = :diagnosis_type,
                    date_diagnosed = :date_diagnosed,
                    philpen_risk_level = :philpen_risk_level,
                    is_smoker = :is_smoker,
                    is_alcohol_drinker = :is_alcohol_drinker,
                    maintenance_meds = :maintenance_meds,
                    target_bp = :target_bp,
                    target_fbs = :target_fbs,
                    status = :status,
                    remarks = :remarks
                WHERE id = :id
            ");
            $uStmt->execute([
                'diagnosis_type' => $diagnosis_type,
                'date_diagnosed' => $date_diagnosed,
                'philpen_risk_level' => $philpen_risk_level,
                'is_smoker' => $is_smoker,
                'is_alcohol_drinker' => $is_alcohol_drinker,
                'maintenance_meds' => $maintenanceMeds = ($maintenance_meds !== '' ? $maintenance_meds : null),
                'target_bp' => $target_bp ?: null,
                'target_fbs' => $target_fbs ?: null,
                'status' => $status,
                'remarks' => $remarks ?: null,
                'id' => $id,
            ]);

            set_flash('success', "NCD Client [{$record['ncd_code']}] successfully updated!");
            header("Location: /HealthLogs/public/ncd/records/index.php");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to update record: ' . $e->getMessage();
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
        <div class="text-2xl font-bold text-slate-900 mt-1">Edit NCD Record: <?= h($record['ncd_code']) ?></div>
        <p class="text-sm text-slate-500 mt-1">Patient: <strong><?= h($record['last_name'] . ', ' . $record['first_name']) ?></strong> (<?= $record['age'] ?>yo, Brgy. <?= h($record['barangay']) ?>)</p>
      </div>
      <span class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center text-xl font-bold">
        <i class="fas fa-pen-to-square"></i>
      </span>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 text-sm">
      <div class="font-bold mb-1"><i class="fas fa-circle-exclamation"></i> Error updating record:</div>
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $err): ?>
          <li><?= h($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl shadow p-5 sm:p-8 space-y-6">
    <!-- Section 1: Clinical Diagnosis & Risk -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-stethoscope text-slate-500"></i> Diagnosis &amp; PhilPEN Assessment
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Diagnosis *</label>
          <select name="diagnosis_type" id="editDiagnosisType" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="hypertension" <?= $record['diagnosis_type'] === 'hypertension' ? 'selected' : '' ?>>Hypertension (HPN)</option>
            <option value="diabetes" <?= $record['diagnosis_type'] === 'diabetes' ? 'selected' : '' ?>>Type 2 Diabetes Mellitus (DM)</option>
            <option value="hypertension_diabetes" <?= $record['diagnosis_type'] === 'hypertension_diabetes' ? 'selected' : '' ?>>Hypertension &amp; Type 2 Diabetes</option>
            <option value="cardiovascular" <?= $record['diagnosis_type'] === 'cardiovascular' ? 'selected' : '' ?>>Cardiovascular Disease (CVD / CAD)</option>
            <option value="asthma_copd" <?= $record['diagnosis_type'] === 'asthma_copd' ? 'selected' : '' ?>>Bronchial Asthma / COPD</option>
            <option value="chronic_kidney" <?= $record['diagnosis_type'] === 'chronic_kidney' ? 'selected' : '' ?>>Chronic Kidney Disease (CKD)</option>
            <option value="cancer" <?= $record['diagnosis_type'] === 'cancer' ? 'selected' : '' ?>>Cancer Registry</option>
            <option value="other" <?= $record['diagnosis_type'] === 'other' ? 'selected' : '' ?>>Other Chronic Condition</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Date Diagnosed *</label>
          <input type="date" name="date_diagnosed" value="<?= h($record['date_diagnosed']) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">PhilPEN CVD Risk Level *</label>
            <span id="editRiskPercentageBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">&lt;10% (Low)</span>
          </div>
          <select name="philpen_risk_level" id="editPhilpenRiskSelect" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="low" <?= $record['philpen_risk_level'] === 'low' ? 'selected' : '' ?>>Low Risk (&lt;10%)</option>
            <option value="moderate" <?= $record['philpen_risk_level'] === 'moderate' ? 'selected' : '' ?>>Moderate Risk (10% to &lt;20%)</option>
            <option value="high" <?= $record['philpen_risk_level'] === 'high' ? 'selected' : '' ?>>High Risk (20% to &lt;30%)</option>
            <option value="very_high" <?= $record['philpen_risk_level'] === 'very_high' ? 'selected' : '' ?>>Very High Risk (&ge;30%)</option>
          </select>
          <div id="editRiskExplanation" class="text-[10px] text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fas fa-heart-pulse text-indigo-500 text-[10px]"></i>
            <span id="editRiskExplanationText">Auto-calculated based on BP &amp; Blood Sugar</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Program Status</label>
          <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="active" <?= $record['status'] === 'active' ? 'selected' : '' ?>>Active (Under Monitoring)</option>
            <option value="controlled" <?= $record['status'] === 'controlled' ? 'selected' : '' ?>>Controlled (Target Achieved)</option>
            <option value="uncontrolled" <?= $record['status'] === 'uncontrolled' ? 'selected' : '' ?>>Uncontrolled / High Alert</option>
            <option value="inactive" <?= $record['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="transferred" <?= $record['status'] === 'transferred' ? 'selected' : '' ?>>Transferred Out</option>
            <option value="deceased" <?= $record['status'] === 'deceased' ? 'selected' : '' ?>>Deceased</option>
          </select>
        </div>

        <!-- Disease Risk Profile & Recommended Medicine Banner -->
        <div class="sm:col-span-2 p-3.5 rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/90 via-sky-50/50 to-blue-50/70 shadow-xs" id="editDiseaseGuideCard">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100 pb-2.5">
            <div class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                <i class="fas fa-notes-medical"></i>
              </span>
              <div>
                <div class="text-[10px] uppercase font-bold text-indigo-700 tracking-wider">Disease Risk Profile</div>
                <div class="text-xs font-bold text-slate-900" id="editDiseaseTitle">Hypertension (HPN)</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-[11px] text-slate-500 font-medium">Risk of Disease:</span>
              <span id="editDiseaseRiskBadge" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
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
                <span id="editDiseaseMedsText" class="font-semibold text-slate-900 ml-1">Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD</span>
                <div class="text-[10px] text-slate-500 mt-0.5" id="editDiseaseNotes">DOH First-line Antihypertensive Protocol for Adults.</div>
              </div>
            </div>
            <button type="button" id="editApplyMedsBtn" class="shrink-0 text-[11px] font-bold text-indigo-700 bg-white hover:bg-indigo-50 active:scale-95 border border-indigo-300 px-3 py-1.5 rounded-lg transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
              <i class="fas fa-prescription-bottle-alt"></i> Apply Medicine
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Lifestyle & Treatment -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-pills text-slate-500"></i> Lifestyle Factors &amp; Maintenance Regimen
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Smoking History</label>
          <select name="is_smoker" id="editIsSmoker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= $record['is_smoker'] === 'never' ? 'selected' : '' ?>>Never Smoked</option>
            <option value="former" <?= $record['is_smoker'] === 'former' ? 'selected' : '' ?>>Former Smoker (Quit)</option>
            <option value="current" <?= $record['is_smoker'] === 'current' ? 'selected' : '' ?>>Current Smoker</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alcohol History</label>
          <select name="is_alcohol_drinker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= $record['is_alcohol_drinker'] === 'never' ? 'selected' : '' ?>>Non-Drinker</option>
            <option value="occasional" <?= $record['is_alcohol_drinker'] === 'occasional' ? 'selected' : '' ?>>Occasional Drinker</option>
            <option value="regular" <?= $record['is_alcohol_drinker'] === 'regular' ? 'selected' : '' ?>>Regular Drinker</option>
            <option value="heavy" <?= $record['is_alcohol_drinker'] === 'heavy' ? 'selected' : '' ?>>Heavy Binge Drinker</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Maintenance Medications</label>
            <span class="text-[10px] text-slate-400">Click a chip below to insert medicine</span>
          </div>
          <input type="text" name="maintenance_meds" id="editMaintenanceMeds" value="<?= h($record['maintenance_meds'] ?? '') ?>" placeholder="e.g. Losartan 50mg OD, Amlodipine 5mg OD" class="w-full border rounded-lg px-3 py-2 text-sm" />
          <div class="mt-1.5 flex flex-wrap items-center gap-1.5" id="editMedsChipContainer"></div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Pressure / BP (mmHg)</label>
          <input type="text" name="target_bp" id="editTargetBp" value="<?= h($record['target_bp'] ?? '') ?>" placeholder="e.g. 120/80" class="w-full border rounded-lg px-3 py-2 text-sm" />
          <span class="text-[10px] text-slate-400">Actual BP upon enrollment</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Sugar / FBS (mg/dL)</label>
          <input type="text" name="target_fbs" id="editTargetFbs" value="<?= h($record['target_fbs'] ?? '') ?>" placeholder="e.g. 100" class="w-full border rounded-lg px-3 py-2 text-sm" />
          <span class="text-[10px] text-slate-400">Actual FBS upon enrollment</span>
        </div>

        <!-- Live PhilPEN CVD Risk Preview -->
        <div class="sm:col-span-2 p-3 rounded-xl border bg-slate-50 border-slate-200 flex items-center justify-between" id="editVitalsRiskBanner">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
              <i class="fas fa-calculator"></i>
            </div>
            <div>
              <div class="text-[10px] uppercase font-bold text-slate-400">Calculated PhilPEN CVD Risk Level</div>
              <div class="text-xs font-semibold text-slate-800" id="editVitalsRiskSummary">Enter BP &amp; FBS above to assess CVD risk</div>
            </div>
          </div>
          <span id="editVitalsRiskBadge" class="text-xs font-bold px-3 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
            &lt;10% Risk
          </span>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Remarks / Notes</label>
          <textarea name="remarks" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($record['remarks'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
      <a href="/HealthLogs/public/ncd/records/index.php" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow transition inline-flex items-center gap-1.5">
        <i class="fas fa-save"></i> Save Changes
      </button>
    </div>
  </form>
</div>

<script>
(function initEditRiskCalculator() {
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

  function updateDiseaseGuidance(diagVal, isManualUserChange = false) {
    const proto = NCD_PROTOCOLS[diagVal] || NCD_PROTOCOLS['hypertension'];

    const titleEl = document.getElementById('editDiseaseTitle');
    const badgeEl = document.getElementById('editDiseaseRiskBadge');
    const medsTextEl = document.getElementById('editDiseaseMedsText');
    const notesEl = document.getElementById('editDiseaseNotes');
    const medsInput = document.getElementById('editMaintenanceMeds');
    const chipContainer = document.getElementById('editMedsChipContainer');

    if (titleEl) titleEl.textContent = proto.title;
    if (badgeEl) {
      badgeEl.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full border ' + proto.riskBadgeClass;
      badgeEl.textContent = proto.riskPercentage + ' (' + proto.baseRiskLevel.replace('_', ' ').toUpperCase() + ' RISK)';
    }
    if (medsTextEl) medsTextEl.textContent = proto.meds;
    if (notesEl) notesEl.textContent = proto.notes;

    // Auto-fill meds if empty or if previously set by auto-fill and user manually changes disease
    if (medsInput && (!medsInput.value.trim() || (isManualUserChange && medsInput.value.trim() === lastAutoMeds))) {
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
    const bpInput = document.getElementById('editTargetBp');
    const fbsInput = document.getElementById('editTargetFbs');
    const smokerSelect = document.getElementById('editIsSmoker');
    const diagSelect = document.getElementById('editDiagnosisType');
    const riskSelect = document.getElementById('editPhilpenRiskSelect');
    const badge = document.getElementById('editRiskPercentageBadge');
    const explainText = document.getElementById('editRiskExplanationText');
    const bannerSummary = document.getElementById('editVitalsRiskSummary');
    const bannerBadge = document.getElementById('editVitalsRiskBadge');

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

  const bpEl = document.getElementById('editTargetBp');
  const fbsEl = document.getElementById('editTargetFbs');
  const smokerEl = document.getElementById('editIsSmoker');
  const diagEl = document.getElementById('editDiagnosisType');
  const riskSelectEl = document.getElementById('editPhilpenRiskSelect');
  const applyBtn = document.getElementById('editApplyMedsBtn');

  if (diagEl) {
    diagEl.addEventListener('change', function() {
      updateDiseaseGuidance(this.value, true);
      recalculateRisk();
    });
  }

  if (applyBtn) {
    applyBtn.addEventListener('click', function() {
      const diagVal = diagEl ? diagEl.value : 'hypertension';
      const proto = NCD_PROTOCOLS[diagVal] || NCD_PROTOCOLS['hypertension'];
      const medsInput = document.getElementById('editMaintenanceMeds');
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
      const badge = document.getElementById('editRiskPercentageBadge');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full border ' + item.color;
        badge.textContent = item.pct;
      }
      const bannerBadge = document.getElementById('editVitalsRiskBadge');
      if (bannerBadge) {
        bannerBadge.className = 'text-xs font-bold px-3 py-1 rounded-lg border shrink-0 ' + item.color;
        bannerBadge.textContent = item.pct + ' (' + this.value.toUpperCase() + ')';
      }
    });
  }

  // Initial populate with existing record diagnosis
  const initialDiag = diagEl ? diagEl.value : 'hypertension';
  updateDiseaseGuidance(initialDiag, false);
  recalculateRisk();
})();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
