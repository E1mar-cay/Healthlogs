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
            <select name="patient_id" id="modalPatientSelect" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
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
            <select name="diagnosis_type" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
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
            <label class="block text-xs font-semibold text-slate-700 mb-1">PhilPEN CVD Risk Level *</label>
            <select name="philpen_risk_level" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
              <option value="low">Low Risk (&lt;10%)</option>
              <option value="moderate">Moderate Risk (10% to &lt;20%)</option>
              <option value="high">High Risk (20% to &lt;30%)</option>
              <option value="very_high">Very High Risk (&ge;30%)</option>
            </select>
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
            <select name="is_smoker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
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
            <label class="block text-xs font-semibold text-slate-700 mb-1">Maintenance Medications</label>
            <input type="text" name="maintenance_meds" placeholder="e.g. Losartan 50mg OD, Amlodipine 5mg OD, Metformin 500mg BID" class="w-full border rounded-lg px-3 py-2 text-sm" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Target BP (mmHg)</label>
            <input type="text" name="target_bp" value="<140/90" placeholder="<140/90" class="w-full border rounded-lg px-3 py-2 text-sm" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Target FBS (mg/dL)</label>
            <input type="text" name="target_fbs" value="<126 mg/dL" placeholder="<126 mg/dL" class="w-full border rounded-lg px-3 py-2 text-sm" />
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
</script>
