<?php
// Shared Enroll NCD Patient Modal Component
$currYear = date('Y');
$modalNextNcdCode = 'NCD-' . $currYear . '-0001';
try {
    $lastCode = $pdo->query("SELECT ncd_code FROM ncd_records WHERE ncd_code LIKE 'NCD-{$currYear}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($lastCode && preg_match('/NCD-\d{4}-(\d+)/', $lastCode, $m)) {
        $seq = (int)$m[1] + 1;
        $modalNextNcdCode = sprintf('NCD-%s-%04d', $currYear, $seq);
    }
} catch (Throwable $e) {}

$modalPatients = [];
try {
    $modalPatients = $pdo->query("
        SELECT id, first_name, last_name, middle_name, birth_date, barangay, sex, contact_no 
        FROM patients 
        WHERE status = 'active'
        ORDER BY last_name ASC, first_name ASC
    ")->fetchAll();
} catch (Throwable $e) {}
?>

<!-- Enroll NCD Client Modal -->
<div id="enrollNcdModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-8 overflow-hidden animate-in fade-in zoom-in duration-150 border border-slate-200">
    <!-- Modal Header -->
    <div class="px-5 py-4 bg-white border-b border-slate-200 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center text-sm font-bold shadow-xs">
          <i class="fas fa-heart-pulse"></i>
        </span>
        <div>
          <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">DOH PhilPEN NCD Registry</div>
          <h3 class="text-base sm:text-lg font-bold text-slate-900">Enroll Non-Communicable Disease Patient</h3>
        </div>
      </div>
      <button type="button" onclick="closeEnrollModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center text-lg font-bold transition">
        &times;
      </button>
    </div>

    <!-- Modal Form -->
    <form action="/HealthLogs/public/ncd/records/save.php" method="POST" class="p-5 sm:p-6 space-y-4 max-h-[80vh] overflow-y-auto">
      <input type="hidden" name="return_to" value="<?= h($_SERVER['REQUEST_URI'] ?? '/HealthLogs/public/ncd.php') ?>" />

      <!-- Section 1: Identification -->
      <div>
        <div class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-2.5 flex items-center gap-1.5 border-b border-slate-200 pb-1">
          <i class="fas fa-id-card text-slate-500"></i> 1. Patient Identification
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Select Patient *</label>
            <select name="patient_id" id="modalPatientSelect" required data-searchable-select data-search-placeholder="Search patient name or barangay..." class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
              <option value="">-- Choose Registered Patient --</option>
              <?php foreach ($modalPatients as $p): ?>
                <?php 
                  $age = $p['birth_date'] ? (date('Y') - date('Y', strtotime($p['birth_date']))) : '';
                ?>
                <option value="<?= (int)$p['id'] ?>">
                  <?= h($p['last_name'] . ', ' . $p['first_name'] . ' (' . ($p['sex'] === 'male' ? 'M' : 'F') . ', ' . $age . 'yo) - Brgy. ' . $p['barangay']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">NCD Registry Code *</label>
            <input type="text" name="ncd_code" value="<?= h($modalNextNcdCode) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400" />
            <span class="text-[10px] text-slate-400">Unique Clinic Registry ID</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Date *</label>
            <input type="date" name="registration_date" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
          </div>
        </div>
      </div>

      <!-- Section 2: Clinical Diagnosis & Risk -->
      <div>
        <div class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-2.5 flex items-center gap-1.5 border-b border-slate-200 pb-1">
          <i class="fas fa-stethoscope text-slate-500"></i> 2. Diagnosis &amp; PhilPEN Assessment
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Diagnosis *</label>
            <select name="diagnosis_type" id="modalDiagnosisType" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
              <option value="hypertension">Hypertension (HPN)</option>
              <option value="diabetes">Type 2 Diabetes Mellitus (DM)</option>
              <option value="hypertension_diabetes">Hypertension &amp; Type 2 Diabetes</option>
              <option value="cardiovascular">Cardiovascular Disease (CVD / CAD)</option>
              <option value="asthma_copd">Bronchial Asthma / COPD</option>
              <option value="chronic_kidney">Chronic Kidney Disease (CKD)</option>
              <option value="cancer">Cancer Registry</option>
              <option value="other">Other Chronic Condition</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Date Diagnosed *</label>
            <input type="date" name="date_diagnosed" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white" />
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700">PhilPEN CVD Risk Level *</label>
              <span id="modalRiskPercentageBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">&lt;10% (Low)</span>
            </div>
            <select name="philpen_risk_level" id="modalPhilpenRiskSelect" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
              <option value="low">Low Risk (&lt;10%)</option>
              <option value="moderate">Moderate Risk (10% to &lt;20%)</option>
              <option value="high">High Risk (20% to &lt;30%)</option>
              <option value="very_high">Very High Risk (&ge;30%)</option>
            </select>
            <div id="modalRiskExplanation" class="text-[10px] text-slate-500 mt-1 flex items-center gap-1.5">
              <i class="fas fa-heart-pulse text-indigo-500 text-[10px]"></i>
              <span id="modalRiskExplanationText">Auto-calculated based on BP &amp; Blood Sugar</span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Program Status</label>
            <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
              <option value="active">Active (Under Monitoring)</option>
              <option value="controlled">Controlled (Target Achieved)</option>
              <option value="uncontrolled">Uncontrolled / High Alert</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <!-- Disease Risk Profile & Recommended Medicine Banner -->
          <div class="sm:col-span-2 p-3 rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/90 via-sky-50/50 to-blue-50/70 shadow-xs" id="modalDiseaseGuideCard">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100 pb-2">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                  <i class="fas fa-notes-medical"></i>
                </span>
                <div>
                  <div class="text-[10px] uppercase font-bold text-indigo-700 tracking-wider">Disease Risk Profile</div>
                  <div class="text-xs font-bold text-slate-900" id="modalDiseaseTitle">Hypertension (HPN)</div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-500 font-medium">Risk of Disease:</span>
                <span id="modalDiseaseRiskBadge" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
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
                  <span id="modalDiseaseMedsText" class="font-semibold text-slate-900 ml-1">Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD</span>
                  <div class="text-[10px] text-slate-500 mt-0.5" id="modalDiseaseNotes">DOH First-line Antihypertensive Protocol for Adults.</div>
                </div>
              </div>
              <button type="button" id="modalApplyMedsBtn" class="shrink-0 text-[11px] font-bold text-indigo-700 bg-white hover:bg-indigo-50 active:scale-95 border border-indigo-300 px-3 py-1.5 rounded-lg transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-prescription-bottle-alt"></i> Apply Medicine
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Risk Factors & Maintenance -->
      <div>
        <div class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-2.5 flex items-center gap-1.5 border-b border-slate-200 pb-1">
          <i class="fas fa-pills text-slate-500"></i> 3. Lifestyle Factors &amp; Treatment
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Smoking History</label>
            <select name="is_smoker" id="modalIsSmoker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
              <option value="never">Never Smoked</option>
              <option value="former">Former Smoker (Quit)</option>
              <option value="current">Current Smoker</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alcohol Consumption</label>
            <select name="is_alcohol_drinker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
              <option value="never">Non-Drinker</option>
              <option value="occasional">Occasional Drinker</option>
              <option value="regular">Regular Drinker</option>
              <option value="heavy">Heavy Binge Drinker</option>
            </select>
          </div>

          <div class="sm:col-span-2">
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700">Maintenance Medications</label>
              <span class="text-[10px] text-slate-400">Click a chip below to insert medicine</span>
            </div>
            <input type="text" name="maintenance_meds" id="modalMaintenanceMeds" placeholder="e.g. Losartan 50mg OD, Amlodipine 5mg OD, Metformin 500mg BID" class="w-full border rounded-lg px-3 py-2 text-sm" />
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5" id="modalMedsChipContainer"></div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Pressure / BP (mmHg)</label>
            <input type="text" name="target_bp" id="modalTargetBp" value="" placeholder="e.g. 120/80" class="w-full border rounded-lg px-3 py-2 text-sm" />
            <span class="text-[10px] text-slate-400">Actual BP upon enrollment</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Sugar / FBS (mg/dL)</label>
            <input type="text" name="target_fbs" id="modalTargetFbs" value="" placeholder="e.g. 100" class="w-full border rounded-lg px-3 py-2 text-sm" />
            <span class="text-[10px] text-slate-400">Actual FBS upon enrollment</span>
          </div>

          <!-- Live PhilPEN CVD Risk Calculation Preview in Section 3 -->
          <div class="sm:col-span-2 p-2.5 rounded-xl border bg-slate-50 border-slate-200 flex items-center justify-between" id="modalVitalsRiskBanner">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                <i class="fas fa-calculator"></i>
              </div>
              <div>
                <div class="text-[10px] uppercase font-bold text-slate-400">Calculated PhilPEN CVD Risk Level</div>
                <div class="text-xs font-semibold text-slate-800" id="modalVitalsRiskSummary">Enter BP &amp; FBS above to assess CVD risk</div>
              </div>
            </div>
            <span id="modalVitalsRiskBadge" class="text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
              &lt;10% Risk
            </span>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Remarks / Diet Notes</label>
            <textarea name="remarks" rows="2" placeholder="e.g. Advised low-salt low-fat diet, daily 30 min brisk walk..." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeEnrollModal()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
          Cancel
        </button>
        <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition inline-flex items-center gap-1.5">
          <i class="fas fa-check"></i> Save &amp; Enroll Client
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEnrollModal() {
  const modal = document.getElementById('enrollNcdModal');
  if (modal) {
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }
}

function closeEnrollModal() {
  const modal = document.getElementById('enrollNcdModal');
  if (modal) {
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }
}

// Close on escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeEnrollModal();
  }
});

// Real-time PhilPEN CVD Risk Calculator & Disease Treatment Protocols
(function initPhilpenRiskCalculator() {
  const NCD_PROTOCOLS = {
    'hypertension': {
      title: 'Hypertension (HPN)',
      baseRiskLevel: 'moderate',
      riskPercentage: '10% to <20%',
      riskBadgeClass: 'bg-amber-100 text-amber-800 border-amber-200',
      meds: 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD',
      medsChips: ['Losartan 50mg OD', 'Amlodipine 5mg OD'],
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

    const titleEl = document.getElementById('modalDiseaseTitle');
    const badgeEl = document.getElementById('modalDiseaseRiskBadge');
    const medsTextEl = document.getElementById('modalDiseaseMedsText');
    const notesEl = document.getElementById('modalDiseaseNotes');
    const medsInput = document.getElementById('modalMaintenanceMeds');
    const chipContainer = document.getElementById('modalMedsChipContainer');

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

  function recalculateModalRisk() {
    const bpInput = document.getElementById('modalTargetBp');
    const fbsInput = document.getElementById('modalTargetFbs');
    const smokerSelect = document.getElementById('modalIsSmoker');
    const diagSelect = document.getElementById('modalDiagnosisType');
    const riskSelect = document.getElementById('modalPhilpenRiskSelect');
    const badge = document.getElementById('modalRiskPercentageBadge');
    const explainText = document.getElementById('modalRiskExplanationText');
    const bannerSummary = document.getElementById('modalVitalsRiskSummary');
    const bannerBadge = document.getElementById('modalVitalsRiskBadge');

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
      vitalsReason = isCrisisBp ? 'Crisis BP (≥180/110)' : 'Stage 2 BP with Diabetic FBS';
    } else if (isStage2Bp || (isStage1Bp && isDiabetic) || isSevereFbs || (isStage1Bp && isSmoker)) {
      vitalsRank = 3;
      vitalsReason = isStage2Bp ? 'Stage 2 HTN (≥160/100)' : (isStage1Bp && isDiabetic ? 'HTN with Diabetic FBS' : 'Elevated vitals');
    } else if (isStage1Bp || isDiabetic || (isElevatedBp && isSmoker) || (fbs !== null && fbs >= 100 && isElevatedBp)) {
      vitalsRank = 2;
      vitalsReason = isStage1Bp ? 'Stage 1 HTN (≥140/90)' : (isDiabetic ? 'Diabetic Blood Sugar' : 'Pre-hypertension');
    } else if (sys !== null || fbs !== null) {
      vitalsRank = 1;
      vitalsReason = 'Controlled vitals';
    }

    const hasVitals = (sys !== null || fbs !== null);
    const finalRank = hasVitals ? Math.max(diseaseRank, vitalsRank) : diseaseRank;

    let level = 'low';
    let percentText = '<10%';
    let pillText = '<10% Risk (Low)';
    let badgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';

    if (finalRank === 4) {
      level = 'very_high';
      percentText = '≥30%';
      pillText = '≥30% Risk (Very High)';
      badgeColor = 'bg-rose-100 text-rose-800 border-rose-200';
    } else if (finalRank === 3) {
      level = 'high';
      percentText = '20% to <30%';
      pillText = '20% to <30% Risk (High)';
      badgeColor = 'bg-orange-100 text-orange-800 border-orange-200';
    } else if (finalRank === 2) {
      level = 'moderate';
      percentText = '10% to <20%';
      pillText = '10% to <20% Risk (Moderate)';
      badgeColor = 'bg-amber-100 text-amber-800 border-amber-200';
    } else {
      level = 'low';
      percentText = '<10%';
      pillText = '<10% Risk (Low)';
      badgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }

    riskSelect.value = level;

    if (badge) {
      badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full border ' + badgeColor;
      badge.textContent = percentText + ' (' + level.replace('_', ' ').toUpperCase() + ')';
    }

    if (explainText) {
      let parts = [proto.title + ' baseline (' + proto.riskPercentage + ')'];
      if (hasVitals && vitalsReason) parts.push(vitalsReason);
      explainText.textContent = 'Auto-calculated: ' + percentText + ' risk • ' + parts.join(' + ');
    }

    if (bannerSummary) {
      bannerSummary.textContent = proto.title + ' risk assessment: ' + percentText + ' 10-year CVD risk';
    }
    if (bannerBadge) {
      bannerBadge.className = 'text-xs font-bold px-2.5 py-1 rounded-lg border shrink-0 ' + badgeColor;
      bannerBadge.textContent = pillText;
    }
  }

  const bpEl = document.getElementById('modalTargetBp');
  const fbsEl = document.getElementById('modalTargetFbs');
  const smokerEl = document.getElementById('modalIsSmoker');
  const diagEl = document.getElementById('modalDiagnosisType');
  const riskSelectEl = document.getElementById('modalPhilpenRiskSelect');
  const applyMedsBtn = document.getElementById('modalApplyMedsBtn');

  if (diagEl) {
    diagEl.addEventListener('change', function() {
      updateDiseaseGuidance(this.value);
      recalculateModalRisk();
    });
  }

  if (applyMedsBtn) {
    applyMedsBtn.addEventListener('click', function() {
      const diagVal = diagEl ? diagEl.value : 'hypertension';
      const proto = NCD_PROTOCOLS[diagVal] || NCD_PROTOCOLS['hypertension'];
      const medsInput = document.getElementById('modalMaintenanceMeds');
      if (medsInput) {
        medsInput.value = proto.meds;
        lastAutoMeds = proto.meds;
        this.innerHTML = '<i class="fas fa-check text-emerald-600"></i> Applied!';
        setTimeout(() => {
          this.innerHTML = '<i class="fas fa-prescription-bottle-alt"></i> Apply Medicine';
        }, 1500);
      }
    });
  }

  if (bpEl) bpEl.addEventListener('input', recalculateModalRisk);
  if (fbsEl) fbsEl.addEventListener('input', recalculateModalRisk);
  if (smokerEl) smokerEl.addEventListener('change', recalculateModalRisk);

  if (riskSelectEl) {
    riskSelectEl.addEventListener('change', function() {
      const map = {
        'low': { pct: '<10%', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        'moderate': { pct: '10% to <20%', color: 'bg-amber-100 text-amber-800 border-amber-200' },
        'high': { pct: '20% to <30%', color: 'bg-orange-100 text-orange-800 border-orange-200' },
        'very_high': { pct: '≥30%', color: 'bg-rose-100 text-rose-800 border-rose-200' }
      };
      const item = map[this.value] || map.low;
      const badge = document.getElementById('modalRiskPercentageBadge');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full border ' + item.color;
        badge.textContent = item.pct;
      }
      const bannerBadge = document.getElementById('modalVitalsRiskBadge');
      if (bannerBadge) {
        bannerBadge.className = 'text-xs font-bold px-2.5 py-1 rounded-lg border shrink-0 ' + item.color;
        bannerBadge.textContent = item.pct + ' (' + this.value.toUpperCase() + ')';
      }
    });
  }

  // Initial runs
  if (diagEl) updateDiseaseGuidance(diagEl.value);
  recalculateModalRisk();
})();
</script>
